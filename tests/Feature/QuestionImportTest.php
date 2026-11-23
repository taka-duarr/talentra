<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\QuestionType;
use App\Livewire\Admin\Questions\QuestionImport;
use App\Models\Question;
use App\Models\User;
use App\Services\Questions\QuestionImporter;
use App\Services\Questions\QuestionTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class QuestionImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function xlsx(array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'import').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(QuestionTemplate::HEADERS));
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return UploadedFile::fake()->createWithContent('soal.xlsx', file_get_contents($path));
    }

    private function admin(): User
    {
        return User::query()->where('email', 'konten@talentra.test')->firstOrFail();
    }

    public function test_downloaded_template_imports_cleanly(): void
    {
        $this->actingAs($this->admin())->get(route('admin.questions.template'))->assertOk()->assertDownload('template-import-soal-talentra.xlsx');

        $path = tempnam(sys_get_temp_dir(), 'template').'.xlsx';
        app(QuestionTemplate::class)->write($path);

        $result = app(QuestionImporter::class)->analyze($path, 'xlsx');

        $this->assertNull($result['header_error']);
        $this->assertCount(5, $result['rows']);
        $this->assertTrue(collect($result['rows'])->every(fn ($row) => $row['valid']), json_encode(collect($result['rows'])->pluck('errors')));
    }

    public function test_valid_rows_are_imported_with_correct_keys_and_invalid_rows_are_skipped(): void
    {
        $before = Question::query()->count();

        $file = $this->xlsx([
            ['CPNS', 'Tes Intelegensia Umum (TIU)', 'Deret Huruf', 'pg', 'A, C, E, G, ...', 'H', 'I', 'J', 'K', '', 'B', 'Lompat satu huruf.', 'mudah', 'Tim Talentra'],
            ['UTBK-SNBT', 'Penalaran Umum', '', 'pgk', 'Pilih bilangan genap.', '2', '3', '4', '', '', 'A,C', '', '', ''],
            ['UTBK-SNBT', 'Penalaran Umum', '', 'bs', 'Tentukan benar/salah.', 'Pernyataan satu', 'Pernyataan dua', '', '', '', 'S,B', '', '', ''],
            ['UTBK-SNBT', 'Pengetahuan Kuantitatif', '', 'isian', 'Setengah dari 5?', '', '', '', '', '', '2,5|2.5', '', '', ''],
            ['CPNS', 'Tes Karakteristik Pribadi (TKP)', '', 'tkp', 'Sikap Anda?', 'Satu', 'Dua', 'Tiga', '', '', '3,5,1', '', '', ''],
            ['CPNS', 'Mata Uji Fiktif', '', 'pg', 'Soal salah mata uji', 'a', 'b', '', '', '', 'A', '', '', ''],
            ['CPNS', 'Tes Intelegensia Umum (TIU)', '', 'pg', 'Kunci di luar opsi', 'a', 'b', '', '', '', 'E', '', '', ''],
            ['CPNS', 'Tes Intelegensia Umum (TIU)', '', 'esai', 'Tipe tidak dikenal', '', '', '', '', '', 'x', '', '', ''],
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(QuestionImport::class)
            ->set('file', $file)
            ->call('analyze')
            ->assertSet('validCount', 5)
            ->assertSet('invalidCount', 3)
            ->assertSee('Mata uji "Mata Uji Fiktif" tidak ada di kategori CPNS.')
            ->set('status', 'published')
            ->call('import')
            ->assertSet('importedCount', 5);

        $this->assertSame($before + 5, Question::query()->count());
        $imported = Question::query()->latest('id')->take(5)->get()->keyBy(fn ($question) => $question->type->value);

        $single = $imported[QuestionType::SingleChoice->value];
        $this->assertSame(ContentStatus::Published, $single->status);
        $this->assertSame('I', $single->options()->where('is_correct', true)->value('body'));
        $this->assertSame('Deret Huruf', $single->topic->name);
        $this->assertSame(1, $single->difficulty);

        $this->assertSame(['2', '4'], $imported[QuestionType::MultipleChoice->value]->options()->where('is_correct', true)->pluck('body')->all());
        $this->assertSame([false, true], $imported[QuestionType::TrueFalse->value]->options()->pluck('is_correct')->all());
        $this->assertSame('2,5|2.5', $imported[QuestionType::ShortAnswer->value]->answer_text);
        $this->assertSame([3, 5, 1], $imported[QuestionType::Weighted->value]->options()->pluck('weight')->all());

        // The stored upload is removed after importing.
        $this->assertNull($component->get('storedPath'));
    }

    public function test_importing_the_same_file_twice_skips_duplicates(): void
    {
        $rows = [['CPNS', 'Tes Intelegensia Umum (TIU)', '', 'pg', 'Soal unik untuk tes duplikat', 'a', 'b', '', '', '', 'A', '', '', '']];

        Livewire::actingAs($this->admin())->test(QuestionImport::class)
            ->set('file', $this->xlsx($rows))->call('analyze')->call('import')->assertSet('importedCount', 1);

        Livewire::actingAs($this->admin())->test(QuestionImport::class)
            ->set('file', $this->xlsx($rows))->call('analyze')
            ->assertSet('validCount', 0)
            ->assertSee('Soal ini sudah ada di bank soal.');
    }

    public function test_csv_files_are_supported_and_plain_text_is_escaped(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'import').'.csv';
        $handle = fopen($path, 'w');
        fputcsv($handle, QuestionTemplate::HEADERS);
        fputcsv($handle, ['UTBK-SNBT', 'Penalaran Umum', '', 'pg', 'Apakah 2 < 3?', 'Ya', 'Tidak', '', '', '', 'A', '', '', '']);
        fclose($handle);

        Livewire::actingAs($this->admin())->test(QuestionImport::class)
            ->set('file', UploadedFile::fake()->createWithContent('soal.csv', file_get_contents($path)))
            ->call('analyze')
            ->assertSet('validCount', 1)
            ->call('import');

        $this->assertSame('<p>Apakah 2 &lt; 3?</p>', Question::query()->latest('id')->value('body'));
    }

    public function test_file_without_required_headers_is_rejected(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'import').'.csv';
        file_put_contents($path, "judul,isi\nabc,def\n");

        Livewire::actingAs($this->admin())->test(QuestionImport::class)
            ->set('file', UploadedFile::fake()->createWithContent('salah.csv', file_get_contents($path)))
            ->call('analyze')
            ->assertSee('Kolom wajib tidak ditemukan');
    }

    public function test_regular_users_cannot_import(): void
    {
        $student = User::query()->where('email', 'siswa@talentra.test')->firstOrFail();

        $this->actingAs($student)->get(route('admin.questions.import'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.questions.template'))->assertForbidden();
    }
}
