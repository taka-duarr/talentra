<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Chapter;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Manages the content tree: categories > subjects > chapters, plus topics used for analysis.
 */
#[Layout('layouts::admin')]
#[Title('Kategori & mata uji')]
class Catalog extends Component
{
    #[Url(as: 'kategori')]
    public ?int $selectedCategoryId = null;

    /** @var array{id: int|null, name: string, description: string, color: string, sort_order: int, is_active: bool} */
    public array $categoryForm = [];

    public string $newSubject = '';

    /** @var array<int, string> */
    public array $newChapter = [];

    /** @var array<int, string> */
    public array $newTopic = [];

    /** @var array<int, int|null> */
    public array $newTopicChapter = [];

    public function mount(): void
    {
        $this->selectedCategoryId ??= Category::query()->ordered()->value('id');
        $this->fillCategoryForm();
    }

    #[Computed]
    public function categories()
    {
        return Category::query()->ordered()->withCount('subjects')->get();
    }

    #[Computed]
    public function selectedCategory(): ?Category
    {
        return $this->selectedCategoryId
            ? Category::query()->with(['subjects.chapters', 'subjects.topics.chapter'])->find($this->selectedCategoryId)
            : null;
    }

    public function selectCategory(?int $id): void
    {
        $this->selectedCategoryId = $id;
        $this->fillCategoryForm();
        unset($this->selectedCategory);
    }

    public function newCategory(): void
    {
        $this->selectedCategoryId = null;
        $this->fillCategoryForm();
    }

    public function saveCategory(): void
    {
        $id = $this->categoryForm['id'] ?? null;

        $validated = $this->validate([
            'categoryForm.name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($id)],
            'categoryForm.description' => ['nullable', 'string', 'max:255'],
            'categoryForm.color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'categoryForm.sort_order' => ['required', 'integer', 'min:0'],
            'categoryForm.is_active' => ['boolean'],
        ])['categoryForm'];

        $category = Category::query()->updateOrCreate(['id' => $id], [
            ...$validated,
            'slug' => Str::slug($validated['name']),
        ]);

        $this->selectedCategoryId = $category->id;
        $this->fillCategoryForm();
        unset($this->categories, $this->selectedCategory);
        session()->flash('status', 'Kategori disimpan.');
    }

    public function addSubject(): void
    {
        $this->validate(['newSubject' => ['required', 'string', 'max:120']]);

        $this->selectedCategory->subjects()->create([
            'name' => $this->newSubject,
            'slug' => Str::slug($this->newSubject),
            'sort_order' => $this->selectedCategory->subjects->count(),
        ]);

        $this->newSubject = '';
        unset($this->selectedCategory);
    }

    public function renameSubject(int $subjectId, string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            return;
        }

        $this->subject($subjectId)->update(['name' => $name, 'slug' => Str::slug($name)]);
        unset($this->selectedCategory);
    }

    public function deleteSubject(int $subjectId): void
    {
        $subject = $this->subject($subjectId);

        if ($subject->questions()->exists()) {
            session()->flash('error', 'Mata uji ini masih punya soal. Pindahkan atau hapus soalnya dulu.');

            return;
        }

        $subject->delete();
        unset($this->selectedCategory);
    }

    public function addChapter(int $subjectId): void
    {
        $title = trim($this->newChapter[$subjectId] ?? '');
        if ($title === '') {
            return;
        }

        $subject = $this->subject($subjectId);
        $subject->chapters()->create(['title' => $title, 'sort_order' => $subject->chapters()->count()]);
        $this->newChapter[$subjectId] = '';
        unset($this->selectedCategory);
    }

    public function renameChapter(int $chapterId, string $title): void
    {
        $title = trim($title);
        if ($title !== '') {
            $this->chapter($chapterId)->update(['title' => $title]);
            unset($this->selectedCategory);
        }
    }

    public function deleteChapter(int $chapterId): void
    {
        $chapter = $this->chapter($chapterId);

        if ($chapter->materials()->exists()) {
            session()->flash('error', 'Bab ini masih punya materi.');

            return;
        }

        $chapter->delete();
        unset($this->selectedCategory);
    }

    public function addTopic(int $subjectId): void
    {
        $name = trim($this->newTopic[$subjectId] ?? '');
        if ($name === '') {
            return;
        }

        $chapterId = $this->newTopicChapter[$subjectId] ?? null;
        $this->subject($subjectId)->topics()->create([
            'name' => $name,
            'chapter_id' => $chapterId && Chapter::query()->where('subject_id', $subjectId)->whereKey($chapterId)->exists() ? $chapterId : null,
        ]);

        $this->newTopic[$subjectId] = '';
        unset($this->selectedCategory);
    }

    public function deleteTopic(int $topicId): void
    {
        Topic::query()->whereKey($topicId)->whereIn('subject_id', $this->selectedCategory->subjects->pluck('id'))->delete();
        unset($this->selectedCategory);
    }

    private function subject(int $id): Subject
    {
        return Subject::query()->where('category_id', $this->selectedCategoryId)->findOrFail($id);
    }

    private function chapter(int $id): Chapter
    {
        return Chapter::query()->whereHas('subject', fn ($query) => $query->where('category_id', $this->selectedCategoryId))->findOrFail($id);
    }

    private function fillCategoryForm(): void
    {
        $category = $this->selectedCategoryId ? Category::query()->find($this->selectedCategoryId) : null;

        $this->categoryForm = [
            'id' => $category?->id,
            'name' => $category->name ?? '',
            'description' => $category->description ?? '',
            'color' => $category->color ?? '#14b8a6',
            'sort_order' => $category->sort_order ?? Category::query()->count(),
            'is_active' => $category->is_active ?? true,
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.catalog');
    }
}
