<?php

namespace App\Livewire\Admin\Questions;

use App\Enums\ContentStatus;
use App\Services\Questions\QuestionImporter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;
use Throwable;

#[Layout('layouts::admin')]
#[Title('Import soal')]
class QuestionImport extends Component
{
    use WithFileUploads;

    /** Rows shown in the preview table; the rest are only counted. */
    public const PREVIEW_LIMIT = 200;

    public $file;

    /** Stored copy of the upload, re-read on import so the preview cannot be tampered with. */
    #[Locked]
    public ?string $storedPath = null;

    #[Locked]
    public ?string $extension = null;

    #[Locked]
    public ?string $originalName = null;

    /** @var array<int, array<string, mixed>> */
    public array $preview = [];

    public int $validCount = 0;

    public int $invalidCount = 0;

    public ?string $headerError = null;

    public string $status = 'review';

    public bool $onlyProblems = false;

    public ?int $importedCount = null;

    public function analyze(QuestionImporter $importer): void
    {
        $this->validate([
            'file' => ['required', 'file', 'max:5120', 'mimes:xlsx,csv,txt'],
        ], [], ['file' => 'file soal']);

        $this->discardStoredFile();
        $this->importedCount = null;

        $this->extension = strtolower($this->file->getClientOriginalExtension());
        $this->originalName = $this->file->getClientOriginalName();
        $this->storedPath = $this->file->store('imports', 'local');
        $this->reset('file');

        try {
            $result = $importer->analyze(Storage::disk('local')->path($this->storedPath), $this->extension);
        } catch (RuntimeException $exception) {
            $this->showError($exception->getMessage());

            return;
        } catch (Throwable) {
            $this->showError('File tidak bisa dibaca. Pastikan formatnya .xlsx atau .csv dari template Talentra.');

            return;
        }

        $this->headerError = $result['header_error'];
        $rows = collect($result['rows']);
        $this->validCount = $rows->where('valid', true)->count();
        $this->invalidCount = $rows->where('valid', false)->count();
        $this->preview = $rows->take(self::PREVIEW_LIMIT)
            ->map(fn (array $row) => collect($row)->except('payload')->all())
            ->all();
    }

    public function import(QuestionImporter $importer): void
    {
        $this->validate(['status' => ['required', Rule::enum(ContentStatus::class)]]);

        if ($this->storedPath === null || ! Storage::disk('local')->exists($this->storedPath)) {
            $this->showError('Unggah file lagi sebelum mengimpor.');

            return;
        }

        $result = $importer->analyze(Storage::disk('local')->path($this->storedPath), $this->extension);
        $this->importedCount = $importer->import($result['rows'], ContentStatus::from($this->status), auth()->user());

        $this->discardStoredFile();
        $this->reset('preview', 'validCount', 'invalidCount', 'headerError', 'originalName', 'onlyProblems');

        session()->flash('status', $this->importedCount.' soal berhasil diimpor.');
    }

    public function cancel(): void
    {
        $this->discardStoredFile();
        $this->reset('preview', 'validCount', 'invalidCount', 'headerError', 'originalName', 'importedCount', 'onlyProblems');
    }

    private function showError(string $message): void
    {
        $this->discardStoredFile();
        $this->reset('preview', 'validCount', 'invalidCount');
        $this->headerError = $message;
    }

    private function discardStoredFile(): void
    {
        if ($this->storedPath !== null) {
            Storage::disk('local')->delete($this->storedPath);
            $this->storedPath = null;
        }
    }

    public function render(): View
    {
        return view('livewire.admin.questions.import', [
            'rows' => $this->onlyProblems ? array_filter($this->preview, fn ($row) => ! $row['valid']) : $this->preview,
            'statuses' => ContentStatus::cases(),
            'totalRows' => $this->validCount + $this->invalidCount,
        ]);
    }
}
