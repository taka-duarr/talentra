<?php

namespace App\Livewire\Admin\Packages;

use App\Enums\ContentStatus;
use App\Models\Category;
use App\Models\ExamPackage;
use App\Models\ExamSection;
use App\Models\Question;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin')]
class PackageForm extends Component
{
    public ?ExamPackage $package = null;

    /** @var array<string, mixed> */
    public array $form = [
        'category_id' => null,
        'title' => '',
        'slug' => '',
        'description' => '',
        'duration_minutes' => 60,
        'is_free' => false,
        'max_attempts' => null,
        'shuffle_questions' => true,
        'shuffle_options' => false,
        'counts_for_ranking' => true,
        'score_correct' => 4,
        'score_wrong' => 0,
        'score_blank' => 0,
        'passing_score' => null,
        'status' => 'draft',
    ];

    /** @var array{name: string, subject_id: int|null, passing_score: int|null} */
    public array $newSection = ['name' => '', 'subject_id' => null, 'passing_score' => null];

    public ?int $activeSectionId = null;

    public string $questionSearch = '';

    public function mount(?ExamPackage $package = null): void
    {
        if ($package?->exists) {
            $this->package = $package;
            $this->form = array_merge($this->form, $package->only(array_keys($this->form)));
            $this->form['status'] = $package->status->value;
            $this->activeSectionId = $package->sections()->value('id');
        }
    }

    #[Computed]
    public function sections()
    {
        return $this->package?->sections()->with(['subject', 'questions.topic'])->get() ?? collect();
    }

    #[Computed]
    public function activeSection(): ?ExamSection
    {
        return $this->sections->firstWhere('id', $this->activeSectionId);
    }

    #[Computed]
    public function availableQuestions()
    {
        if (! $this->activeSection) {
            return collect();
        }

        $usedIds = $this->activeSection->questions->pluck('id');

        return Question::query()
            ->published()
            ->with('topic')
            ->whereHas('subject', fn ($query) => $query->where('category_id', $this->package->category_id))
            ->when($this->activeSection->subject_id, fn ($query, $subjectId) => $query->where('subject_id', $subjectId))
            ->whereNotIn('id', $usedIds)
            ->when($this->questionSearch !== '', fn ($query) => $query->where('body', 'like', '%'.$this->questionSearch.'%'))
            ->latest('id')
            ->limit(30)
            ->get();
    }

    public function updatedFormTitle(string $value): void
    {
        if (! $this->package) {
            $this->form['slug'] = Str::slug($value);
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'form.category_id' => ['required', Rule::exists('categories', 'id')],
            'form.title' => ['required', 'string', 'max:255'],
            'form.slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('exam_packages', 'slug')->ignore($this->package?->id)],
            'form.description' => ['nullable', 'string', 'max:1000'],
            'form.duration_minutes' => ['required', 'integer', 'between:1,600'],
            'form.is_free' => ['boolean'],
            'form.max_attempts' => ['nullable', 'integer', 'between:1,100'],
            'form.shuffle_questions' => ['boolean'],
            'form.shuffle_options' => ['boolean'],
            'form.counts_for_ranking' => ['boolean'],
            'form.score_correct' => ['required', 'integer', 'between:0,100'],
            'form.score_wrong' => ['required', 'integer', 'between:-100,100'],
            'form.score_blank' => ['required', 'integer', 'between:-100,100'],
            'form.passing_score' => ['nullable', 'integer', 'min:0'],
            'form.status' => ['required', Rule::enum(ContentStatus::class)],
        ])['form'];

        $validated['max_attempts'] = $validated['max_attempts'] ?: null;
        $validated['passing_score'] = $validated['passing_score'] === '' ? null : $validated['passing_score'];

        if ($validated['status'] === ContentStatus::Published->value) {
            $validated['published_at'] = $this->package?->published_at ?? now();
        }

        if ($this->package) {
            $this->package->update($validated);
        } else {
            $this->package = ExamPackage::create($validated);
        }

        session()->flash('status', 'Paket disimpan.');
        $this->redirectRoute('admin.packages.edit', $this->package, navigate: true);
    }

    public function addSection(): void
    {
        $this->validate([
            'newSection.name' => ['required', 'string', 'max:120'],
            'newSection.subject_id' => ['nullable', Rule::exists('subjects', 'id')->where('category_id', $this->package->category_id)],
            'newSection.passing_score' => ['nullable', 'integer', 'min:0'],
        ]);

        $section = $this->package->sections()->create([
            'name' => $this->newSection['name'],
            'subject_id' => $this->newSection['subject_id'] ?: null,
            'passing_score' => $this->newSection['passing_score'] ?: null,
            'sort_order' => $this->sections->count(),
        ]);

        $this->newSection = ['name' => '', 'subject_id' => null, 'passing_score' => null];
        $this->activeSectionId = $section->id;
        unset($this->sections);
    }

    public function updateSectionPassing(int $sectionId, ?string $value): void
    {
        $this->section($sectionId)->update(['passing_score' => $value === '' || $value === null ? null : max(0, (int) $value)]);
        unset($this->sections);
    }

    public function deleteSection(int $sectionId): void
    {
        $this->section($sectionId)->delete();
        $this->activeSectionId = $this->package->sections()->value('id');
        unset($this->sections);
    }

    public function selectSection(int $sectionId): void
    {
        $this->activeSectionId = $this->section($sectionId)->id;
        $this->questionSearch = '';
    }

    public function attachQuestion(int $questionId): void
    {
        $section = $this->section($this->activeSectionId);
        $section->questions()->syncWithoutDetaching([$questionId => ['sort_order' => $section->questions()->count()]]);
        unset($this->sections, $this->activeSection);
    }

    public function detachQuestion(int $questionId): void
    {
        $this->section($this->activeSectionId)->questions()->detach($questionId);
        unset($this->sections, $this->activeSection);
    }

    private function section(?int $id): ExamSection
    {
        return ExamSection::query()->where('exam_package_id', $this->package?->id)->findOrFail($id);
    }

    public function render(): View
    {
        return view('livewire.admin.packages.form', [
            'categories' => Category::query()->ordered()->get(['id', 'name']),
            'subjects' => $this->package ? $this->package->category->subjects : collect(),
            'statuses' => ContentStatus::cases(),
        ])->title($this->package ? 'Kelola '.$this->package->title : 'Paket baru');
    }
}
