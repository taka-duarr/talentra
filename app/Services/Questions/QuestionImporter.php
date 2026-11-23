<?php

namespace App\Services\Questions;

use App\Enums\ContentStatus;
use App\Enums\QuestionType;
use App\Models\Category;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Support\Html;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;

/**
 * Reads questions from an .xlsx/.csv file in the layout produced by QuestionTemplate.
 */
class QuestionImporter
{
    public const MAX_ROWS = 1000;

    public const OPTION_COLUMNS = ['opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'opsi_e', 'opsi_f'];

    public const REQUIRED_HEADERS = ['kategori', 'mata_uji', 'tipe', 'soal', 'kunci'];

    private const TYPE_ALIASES = [
        'pg' => QuestionType::SingleChoice, 'pilihan ganda' => QuestionType::SingleChoice, 'single_choice' => QuestionType::SingleChoice,
        'pgk' => QuestionType::MultipleChoice, 'pg kompleks' => QuestionType::MultipleChoice, 'pilihan ganda kompleks' => QuestionType::MultipleChoice, 'multiple_choice' => QuestionType::MultipleChoice,
        'bs' => QuestionType::TrueFalse, 'benar salah' => QuestionType::TrueFalse, 'benar/salah' => QuestionType::TrueFalse, 'true_false' => QuestionType::TrueFalse,
        'isian' => QuestionType::ShortAnswer, 'isian singkat' => QuestionType::ShortAnswer, 'short_answer' => QuestionType::ShortAnswer,
        'tkp' => QuestionType::Weighted, 'berbobot' => QuestionType::Weighted, 'weighted' => QuestionType::Weighted,
    ];

    private const DIFFICULTY_ALIASES = ['1' => 1, 'mudah' => 1, '2' => 2, 'sedang' => 2, '3' => 3, 'sulit' => 3];

    /** @var Collection<int, Category>|null */
    private ?Collection $categories = null;

    /**
     * Parses and validates every row without writing anything.
     *
     * @return array{rows: array<int, array{row: int, valid: bool, errors: array<int, string>, notes: array<int, string>, summary: array<string, string>, payload: array<string, mixed>|null}>, header_error: string|null}
     */
    public function analyze(string $path, string $extension): array
    {
        $raw = $this->readRows($path, $extension);

        if ($raw === []) {
            return ['rows' => [], 'header_error' => 'File kosong atau tidak memiliki baris judul.'];
        }

        $headers = array_map(fn ($value) => Str::of((string) $value)->lower()->trim()->replace([' ', '-'], '_')->toString(), array_shift($raw));
        $missing = array_diff(self::REQUIRED_HEADERS, $headers);

        if ($missing !== []) {
            return ['rows' => [], 'header_error' => 'Kolom wajib tidak ditemukan: '.implode(', ', $missing).'. Gunakan template dari halaman ini.'];
        }

        if (count($raw) > self::MAX_ROWS) {
            return ['rows' => [], 'header_error' => 'Maksimal '.self::MAX_ROWS.' soal per file. File ini berisi '.count($raw).' baris.'];
        }

        $seenInFile = [];
        $rows = [];

        foreach ($raw as $index => $values) {
            $cells = [];
            foreach ($headers as $position => $header) {
                $cells[$header] = trim((string) ($values[$position] ?? ''));
            }

            if (collect($cells)->filter(fn ($value) => $value !== '')->isEmpty()) {
                continue;
            }

            $rows[] = $this->analyzeRow($index + 2, $cells, $seenInFile);
        }

        return ['rows' => $rows, 'header_error' => null];
    }

    /**
     * Imports the valid rows. Returns the number of questions created.
     *
     * @param  array<int, array{valid: bool, payload: array<string, mixed>|null}>  $rows
     */
    public function import(array $rows, ContentStatus $status, User $author): int
    {
        $valid = array_filter($rows, fn (array $row) => $row['valid']);

        return DB::transaction(function () use ($valid, $status, $author) {
            $created = 0;

            foreach ($valid as $row) {
                $payload = $row['payload'];

                $topicId = null;
                if ($payload['topic'] !== null) {
                    $topicId = Topic::query()->firstOrCreate(['subject_id' => $payload['subject_id'], 'name' => $payload['topic']])->id;
                }

                $question = Question::create([
                    'subject_id' => $payload['subject_id'],
                    'topic_id' => $topicId,
                    'type' => $payload['type'],
                    'body' => $payload['body'],
                    'explanation' => $payload['explanation'],
                    'answer_text' => $payload['answer_text'],
                    'difficulty' => $payload['difficulty'],
                    'status' => $status,
                    'source' => $payload['source'],
                    'created_by' => $author->id,
                ]);

                foreach ($payload['options'] as $order => $option) {
                    $question->options()->create([...$option, 'sort_order' => $order]);
                }

                $created++;
            }

            return $created;
        });
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function readRows(string $path, string $extension): array
    {
        $reader = match (strtolower($extension)) {
            'xlsx' => new XlsxReader,
            'csv', 'txt' => new CsvReader,
            default => throw new RuntimeException('Format file tidak didukung. Gunakan .xlsx atau .csv.'),
        };

        return $this->readFirstDataSheet($reader, $path);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function readFirstDataSheet(ReaderInterface $reader, string $path): array
    {
        $reader->open($path);
        $rows = [];

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                if (Str::lower($sheet->getName()) === 'petunjuk') {
                    continue;
                }

                foreach ($sheet->getRowIterator() as $row) {
                    $values = array_map(fn ($value) => $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value, $row->toArray());

                    // Skip leading empty rows before the header.
                    if ($rows === [] && collect($values)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty()) {
                        continue;
                    }

                    $rows[] = $values;

                    if (count($rows) > self::MAX_ROWS + 1) {
                        break 2;
                    }
                }

                break;
            }
        } finally {
            $reader->close();
        }

        return $rows;
    }

    /**
     * @param  array<string, string>  $cells
     * @param  array<string, true>  $seenInFile
     * @return array{row: int, valid: bool, errors: array<int, string>, notes: array<int, string>, summary: array<string, string>, payload: array<string, mixed>|null}
     */
    private function analyzeRow(int $rowNumber, array $cells, array &$seenInFile): array
    {
        $errors = [];
        $notes = [];

        $subject = $this->resolveSubject($cells['kategori'] ?? '', $cells['mata_uji'] ?? '', $errors);

        $type = self::TYPE_ALIASES[Str::lower($cells['tipe'] ?? '')] ?? null;
        if ($type === null) {
            $errors[] = 'Tipe "'.($cells['tipe'] ?? '').'" tidak dikenal. Pakai pg, pgk, bs, isian, atau tkp.';
        }

        if (($cells['soal'] ?? '') === '') {
            $errors[] = 'Kolom soal kosong.';
        }

        $optionTexts = collect(self::OPTION_COLUMNS)->map(fn ($column) => $cells[$column] ?? '')->filter(fn ($value) => $value !== '')->values();

        $difficultyRaw = Str::lower($cells['kesulitan'] ?? '');
        $difficulty = $difficultyRaw === '' ? 2 : (self::DIFFICULTY_ALIASES[$difficultyRaw] ?? null);
        if ($difficulty === null) {
            $errors[] = 'Kesulitan harus 1–3 atau mudah/sedang/sulit.';
        }

        $options = [];
        $answerText = null;
        if ($type !== null) {
            [$options, $answerText] = $this->buildAnswer($type, $optionTexts, $cells['kunci'] ?? '', $errors);
        }

        $topic = ($cells['topik'] ?? '') !== '' ? Str::limit($cells['topik'], 100, '') : null;
        if ($topic !== null && $subject !== null && ! Topic::query()->where('subject_id', $subject->id)->where('name', $topic)->exists()) {
            $notes[] = 'Topik baru "'.$topic.'" akan dibuat.';
        }

        $body = $this->toHtml($cells['soal'] ?? '');

        if ($subject !== null && $body !== '') {
            $fingerprint = $subject->id.'|'.Str::lower(Str::squish(strip_tags($body)));

            if (isset($seenInFile[$fingerprint])) {
                $errors[] = 'Soal yang sama muncul lebih dari sekali di file ini.';
            } elseif (Question::query()->where('subject_id', $subject->id)->where('body', $body)->exists()) {
                $errors[] = 'Soal ini sudah ada di bank soal.';
            }

            $seenInFile[$fingerprint] = true;
        }

        $valid = $errors === [];

        return [
            'row' => $rowNumber,
            'valid' => $valid,
            'errors' => $errors,
            'notes' => $notes,
            'summary' => [
                'subject' => $subject ? $subject->category->name.' › '.$subject->name : ($cells['mata_uji'] ?? ''),
                'type' => $type?->label() ?? ($cells['tipe'] ?? ''),
                'body' => Str::limit(Str::squish(strip_tags($body)), 120),
                'answer' => $cells['kunci'] ?? '',
            ],
            'payload' => $valid ? [
                'subject_id' => $subject->id,
                'topic' => $topic,
                'type' => $type->value,
                'body' => $body,
                'explanation' => ($cells['pembahasan'] ?? '') !== '' ? $this->toHtml($cells['pembahasan']) : null,
                'answer_text' => $answerText,
                'difficulty' => $difficulty,
                'source' => ($cells['sumber'] ?? '') !== '' ? Str::limit($cells['sumber'], 250, '') : null,
                'options' => $options,
            ] : null,
        ];
    }

    /**
     * @param  Collection<int, string>  $optionTexts
     * @param  array<int, string>  $errors
     * @return array{0: array<int, array{body: string, is_correct: bool, weight: int}>, 1: string|null}
     */
    private function buildAnswer(QuestionType $type, Collection $optionTexts, string $key, array &$errors): array
    {
        $key = trim($key);

        if ($type === QuestionType::ShortAnswer) {
            if ($key === '') {
                $errors[] = 'Isi kolom kunci dengan jawaban yang benar.';
            }

            return [[], $key === '' ? null : Str::limit($key, 250, '')];
        }

        $minimum = match ($type) {
            QuestionType::TrueFalse => 1,
            QuestionType::MultipleChoice => 3,
            default => 2,
        };

        if ($optionTexts->count() < $minimum) {
            $errors[] = $type->label().' butuh minimal '.$minimum.' opsi (kolom opsi_a dan seterusnya).';

            return [[], null];
        }

        $tokens = collect(preg_split('/[\s,;]+/', Str::upper($key), -1, PREG_SPLIT_NO_EMPTY));
        $letters = array_slice(['A', 'B', 'C', 'D', 'E', 'F'], 0, $optionTexts->count());
        $correct = [];
        $weights = [];

        switch ($type) {
            case QuestionType::SingleChoice:
                if ($tokens->count() !== 1 || ! in_array($tokens->first(), $letters, true)) {
                    $errors[] = 'Kunci pilihan ganda harus satu huruf antara '.implode('/', $letters).'.';
                }
                $correct = $tokens->all();
                break;

            case QuestionType::MultipleChoice:
                if ($tokens->isEmpty() || $tokens->diff($letters)->isNotEmpty()) {
                    $errors[] = 'Kunci pilihan ganda kompleks berisi huruf opsi yang benar, contoh: A,C.';
                }
                $correct = $tokens->all();
                break;

            case QuestionType::TrueFalse:
                $values = $tokens->map(fn ($token) => match ($token) {
                    'B', 'BENAR', 'TRUE', '1' => true,
                    'S', 'SALAH', 'FALSE', '0' => false,
                    default => null,
                });
                if ($values->count() !== $optionTexts->count() || $values->containsStrict(null)) {
                    $errors[] = 'Kunci benar/salah harus berisi B atau S untuk setiap pernyataan ('.$optionTexts->count().' nilai), contoh: B,S,B.';
                }
                $correct = $values->all();
                break;

            case QuestionType::Weighted:
                $weights = $tokens->map(fn ($token) => ctype_digit($token) ? (int) $token : null);
                if ($weights->count() !== $optionTexts->count() || $weights->contains(fn ($weight) => $weight === null || $weight < 1 || $weight > 5)) {
                    $errors[] = 'Kunci TKP berisi bobot 1–5 untuk setiap opsi ('.$optionTexts->count().' nilai), contoh: 5,4,3,2,1.';
                }
                $weights = $weights->all();
                break;

            default:
                break;
        }

        $options = $optionTexts->map(fn (string $text, int $index) => [
            'body' => $this->optionHtml($text),
            'is_correct' => match ($type) {
                QuestionType::TrueFalse => (bool) ($correct[$index] ?? false),
                QuestionType::Weighted => false,
                default => in_array($letters[$index], $correct, true),
            },
            'weight' => $type === QuestionType::Weighted ? (int) ($weights[$index] ?? 0) : 0,
        ])->all();

        return [$options, null];
    }

    /**
     * @param  array<int, string>  $errors
     */
    private function resolveSubject(string $categoryName, string $subjectName, array &$errors): ?Subject
    {
        $this->categories ??= Category::query()->with('subjects')->get();
        $needle = Str::lower(trim($categoryName));

        $category = $this->categories->first(fn (Category $category) => Str::lower($category->name) === $needle || $category->slug === Str::slug($categoryName));

        if ($category === null) {
            $errors[] = 'Kategori "'.$categoryName.'" tidak ditemukan.';

            return null;
        }

        $subjectNeedle = Str::lower(trim($subjectName));
        $subject = $category->subjects->first(fn (Subject $subject) => Str::lower($subject->name) === $subjectNeedle || $subject->slug === Str::slug($subjectName));

        if ($subject === null) {
            $errors[] = 'Mata uji "'.$subjectName.'" tidak ada di kategori '.$category->name.'.';

            return null;
        }

        return $subject->setRelation('category', $category);
    }

    /**
     * Plain text becomes escaped paragraphs; HTML is passed through the sanitizer.
     */
    private function toHtml(string $text): string
    {
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        if (preg_match('/<\s*[a-z][^>]*>/i', $text)) {
            return Html::clean($text);
        }

        return collect(preg_split('/\R{2,}/', $text))
            ->map(fn ($paragraph) => '<p>'.nl2br(e(trim($paragraph)), false).'</p>')
            ->join('');
    }

    private function optionHtml(string $text): string
    {
        return preg_match('/<\s*[a-z][^>]*>/i', $text) ? Html::clean($text) : e($text);
    }
}
