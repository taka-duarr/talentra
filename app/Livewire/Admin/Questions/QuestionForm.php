<?php

namespace App\Livewire\Admin\Questions;

use App\Enums\ContentStatus;
use App\Enums\QuestionType;
use App\Models\Category;
use App\Models\Question;
use App\Models\Topic;
use App\Support\Html;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin')]
class QuestionForm extends Component
{
    public ?Question $question = null;

    public ?int $subject_id = null;

    public ?int $topic_id = null;

    public string $type = 'single_choice';

    public string $body = '';

    public string $explanation = '';

    public string $answer_text = '';

    public int $difficulty = 2;

    public string $status = 'draft';

    public string $source = '';

    /** @var array<int, array{body: string, is_correct: bool, weight: int}> */
    public array $options = [];

    public function mount(?Question $question = null): void
    {
        if ($question?->exists) {
            $this->question = $question->load('options');
            $this->fill([
                'subject_id' => $question->subject_id,
                'topic_id' => $question->topic_id,
                'type' => $question->type->value,
                'body' => $question->body,
                'explanation' => (string) $question->explanation,
                'answer_text' => (string) $question->answer_text,
                'difficulty' => $question->difficulty,
                'status' => $question->status->value,
                'source' => (string) $question->source,
            ]);
            $this->options = $question->options->map(fn ($option) => [
                'body' => $option->body,
                'is_correct' => $option->is_correct,
                'weight' => $option->weight,
            ])->all();
        } else {
            $this->subject_id = (int) request()->query('mata_uji') ?: null;
            $this->resetOptionsForType();
        }
    }

    #[Computed]
    public function topics()
    {
        return $this->subject_id ? Topic::query()->where('subject_id', $this->subject_id)->orderBy('name')->get() : collect();
    }

    public function updatedSubjectId(): void
    {
        $this->topic_id = null;
    }

    public function updatedType(): void
    {
        if ($this->options === [] || $this->type === QuestionType::ShortAnswer->value) {
            $this->resetOptionsForType();
        }

        if ($this->type === QuestionType::Weighted->value) {
            foreach ($this->options as $index => $option) {
                $this->options[$index]['weight'] = $option['weight'] ?: max(1, 5 - $index);
            }
        }
    }

    public function addOption(): void
    {
        $this->options[] = ['body' => '', 'is_correct' => false, 'weight' => 1];
    }

    public function removeOption(int $index): void
    {
        unset($this->options[$index]);
        $this->options = array_values($this->options);
    }

    public function markCorrect(int $index): void
    {
        foreach ($this->options as $key => $option) {
            $this->options[$key]['is_correct'] = $key === $index;
        }
    }

    public function save(): void
    {
        $type = QuestionType::from($this->type);

        $validated = $this->validate([
            'subject_id' => ['required', Rule::exists('subjects', 'id')],
            'topic_id' => ['nullable', Rule::exists('topics', 'id')->where('subject_id', $this->subject_id)],
            'type' => ['required', Rule::enum(QuestionType::class)],
            'body' => ['required', 'string'],
            'explanation' => ['nullable', 'string'],
            'answer_text' => [Rule::requiredIf($type === QuestionType::ShortAnswer), 'nullable', 'string', 'max:255'],
            'difficulty' => ['required', 'integer', 'between:1,3'],
            'status' => ['required', Rule::enum(ContentStatus::class)],
            'source' => ['nullable', 'string', 'max:255'],
            'options' => [Rule::requiredIf($type->usesOptions()), 'array', 'max:8'],
            'options.*.body' => ['required', 'string'],
            'options.*.weight' => ['integer', 'between:0,5'],
        ], [
            'options.*.body.required' => 'Isi setiap opsi jawaban.',
        ]);

        $this->validateOptionsForType($type);

        DB::transaction(function () use ($validated, $type) {
            $attributes = [
                'subject_id' => $validated['subject_id'],
                'topic_id' => $validated['topic_id'],
                'type' => $type,
                'body' => Html::clean($validated['body']),
                'explanation' => Html::clean($validated['explanation']),
                'answer_text' => $type === QuestionType::ShortAnswer ? trim($validated['answer_text']) : null,
                'difficulty' => $validated['difficulty'],
                'status' => $validated['status'],
                'source' => $validated['source'] ?: null,
            ];

            if ($this->question) {
                $this->question->update($attributes);
            } else {
                $this->question = Question::create([...$attributes, 'created_by' => auth()->id()]);
            }

            $this->question->options()->delete();

            if ($type->usesOptions()) {
                foreach (array_values($this->options) as $index => $option) {
                    $this->question->options()->create([
                        'body' => Html::clean($option['body']),
                        'is_correct' => $type === QuestionType::Weighted ? false : (bool) $option['is_correct'],
                        'weight' => $type === QuestionType::Weighted ? (int) $option['weight'] : 0,
                        'sort_order' => $index,
                    ]);
                }
            }
        });

        session()->flash('status', 'Soal disimpan.');
        $this->redirectRoute('admin.questions.edit', $this->question, navigate: true);
    }

    private function validateOptionsForType(QuestionType $type): void
    {
        $options = collect($this->options);
        $correct = $options->where('is_correct', true)->count();

        $message = match (true) {
            $type === QuestionType::SingleChoice && $options->count() < 2 => 'Pilihan ganda butuh minimal 2 opsi.',
            $type === QuestionType::SingleChoice && $correct !== 1 => 'Pilihan ganda harus punya tepat 1 jawaban benar.',
            $type === QuestionType::MultipleChoice && ($correct < 1 || $options->count() < 3) => 'Pilihan ganda kompleks butuh minimal 3 opsi dan 1 jawaban benar.',
            $type === QuestionType::TrueFalse && $options->count() < 1 => 'Tambahkan minimal 1 pernyataan.',
            $type === QuestionType::Weighted && $options->contains(fn ($option) => (int) $option['weight'] < 1) => 'Setiap opsi TKP harus berbobot 1–5.',
            default => null,
        };

        if ($message !== null) {
            throw ValidationException::withMessages(['options' => $message]);
        }
    }

    private function resetOptionsForType(): void
    {
        $this->options = $this->type === QuestionType::ShortAnswer->value
            ? []
            : collect(range(0, 4))->map(fn ($index) => ['body' => '', 'is_correct' => false, 'weight' => 5 - $index])->all();
    }

    public function render(): View
    {
        return view('livewire.admin.questions.form', [
            'categories' => Category::query()->ordered()->with('subjects')->get(),
            'types' => QuestionType::cases(),
            'statuses' => ContentStatus::cases(),
            'bodyPreview' => Html::clean($this->body),
        ])->title($this->question ? 'Ubah soal #'.$this->question->id : 'Soal baru');
    }
}
