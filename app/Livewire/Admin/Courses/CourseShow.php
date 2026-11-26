<?php

namespace App\Livewire\Admin\Courses;

use App\Enums\ContentStatus;
use App\Enums\QuestionType;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\ExamPackage;
use App\Models\Material;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin')]
class CourseShow extends Component
{
    use WithPagination;

    public Category $category;

    #[Url(as: 'tab')]
    public string $tab = 'kurikulum';

    /** @var array{name: string, description: string, color: string, sort_order: int, is_active: bool} */
    public array $courseForm = [];

    public string $newSubject = '';

    /** @var array<int, string> */
    public array $newChapter = [];

    /** @var array<int, string> */
    public array $newTopic = [];

    /** @var array<int, int|null> */
    public array $newTopicChapter = [];

    #[Url(as: 'modul')]
    public string $subjectFilter = '';

    #[Url(as: 'status_soal')]
    public string $questionStatus = '';

    #[Url(as: 'tipe_soal')]
    public string $questionType = '';

    #[Url(as: 'cari_soal')]
    public string $questionSearch = '';

    public function mount(Category $category): void
    {
        $this->category = $category;
        $this->fillCourseForm();
    }

    public function updatedQuestionSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSubjectFilter(): void
    {
        $this->resetPage();
    }

    public function updatedQuestionStatus(): void
    {
        $this->resetPage();
    }

    public function updatedQuestionType(): void
    {
        $this->resetPage();
    }

    public function switchCourse(string $slug): void
    {
        if ($slug === '' || $slug === $this->category->slug) {
            return;
        }

        $target = Category::query()->where('slug', $slug)->firstOrFail();
        $this->redirectRoute('admin.courses.show', ['category' => $target, 'tab' => $this->tab], navigate: true);
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['kurikulum', 'soal', 'tryout', 'pengaturan'], true)) {
            $this->tab = $tab;
            $this->resetPage();
        }
    }

    public function saveCourse(): void
    {
        $validated = $this->validate([
            'courseForm.name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($this->category->id)],
            'courseForm.description' => ['nullable', 'string', 'max:255'],
            'courseForm.color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'courseForm.sort_order' => ['required', 'integer', 'min:0'],
            'courseForm.is_active' => ['boolean'],
        ])['courseForm'];

        $this->category->update($validated);
        session()->flash('status', 'Pengaturan kursus diperbarui.');
    }

    public function addSubject(): void
    {
        $this->validate(['newSubject' => ['required', 'string', 'max:120']]);

        $this->category->subjects()->create([
            'name' => trim($this->newSubject),
            'slug' => Str::slug($this->newSubject),
            'sort_order' => $this->category->subjects()->count(),
        ]);

        $this->newSubject = '';
        session()->flash('status', 'Modul baru ditambahkan.');
    }

    public function renameSubject(int $subjectId, string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            return;
        }

        $this->subject($subjectId)->update(['name' => $name, 'slug' => Str::slug($name)]);
    }

    public function deleteSubject(int $subjectId): void
    {
        $subject = $this->subject($subjectId);

        if ($subject->questions()->exists() || Material::query()->whereHas('chapter', fn ($q) => $q->where('subject_id', $subject->id))->exists()) {
            session()->flash('error', 'Modul ini masih memiliki materi atau soal. Hapus atau pindahkan isinya terlebih dahulu.');

            return;
        }

        $subject->delete();
        session()->flash('status', 'Modul dihapus.');
    }

    public function addChapter(int $subjectId): void
    {
        $title = trim($this->newChapter[$subjectId] ?? '');
        if ($title === '') {
            return;
        }

        $subject = $this->subject($subjectId);
        $subject->chapters()->create([
            'title' => $title,
            'sort_order' => $subject->chapters()->count(),
        ]);

        $this->newChapter[$subjectId] = '';
    }

    public function renameChapter(int $chapterId, string $title): void
    {
        $title = trim($title);
        if ($title !== '') {
            $this->chapter($chapterId)->update(['title' => $title]);
        }
    }

    public function deleteChapter(int $chapterId): void
    {
        $chapter = $this->chapter($chapterId);

        if ($chapter->materials()->exists()) {
            session()->flash('error', 'Bab ini masih memiliki materi.');

            return;
        }

        $chapter->delete();
    }

    public function deleteMaterial(int $materialId): void
    {
        Material::query()
            ->whereHas('chapter.subject', fn ($query) => $query->where('category_id', $this->category->id))
            ->findOrFail($materialId)
            ->delete();

        session()->flash('status', 'Materi dihapus.');
    }

    public function setQuestionStatus(int $id, string $status): void
    {
        Question::query()
            ->whereHas('subject', fn ($query) => $query->where('category_id', $this->category->id))
            ->findOrFail($id)
            ->update(['status' => ContentStatus::from($status)]);
    }

    public function deleteQuestion(int $id): void
    {
        $question = Question::query()
            ->whereHas('subject', fn ($query) => $query->where('category_id', $this->category->id))
            ->findOrFail($id);

        if ($question->sections()->exists()) {
            session()->flash('error', 'Soal ini dipakai di paket Try Out. Keluarkan dari paket terlebih dahulu.');

            return;
        }

        $question->delete();
        session()->flash('status', 'Soal dihapus.');
    }

    public function deletePackage(int $id): void
    {
        $package = ExamPackage::query()
            ->where('category_id', $this->category->id)
            ->withCount('attempts')
            ->findOrFail($id);

        if ($package->attempts_count > 0) {
            session()->flash('error', 'Paket sudah pernah dikerjakan peserta. Ubah statusnya menjadi draf.');

            return;
        }

        $package->delete();
        session()->flash('status', 'Paket Try Out dihapus.');
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
    }

    public function deleteTopic(int $topicId): void
    {
        Topic::query()
            ->whereKey($topicId)
            ->whereHas('subject', fn ($query) => $query->where('category_id', $this->category->id))
            ->delete();
    }

    private function subject(int $id): Subject
    {
        return Subject::query()->where('category_id', $this->category->id)->findOrFail($id);
    }

    private function chapter(int $id): Chapter
    {
        return Chapter::query()
            ->whereHas('subject', fn ($query) => $query->where('category_id', $this->category->id))
            ->findOrFail($id);
    }

    private function fillCourseForm(): void
    {
        $this->courseForm = [
            'name' => $this->category->name,
            'description' => (string) $this->category->description,
            'color' => $this->category->color,
            'sort_order' => $this->category->sort_order,
            'is_active' => $this->category->is_active,
        ];
    }

    public function render(): View
    {
        $this->category->load([
            'subjects.chapters.materials',
            'subjects.topics.chapter',
        ]);

        $materialsCount = $this->category->subjects->sum(fn (Subject $s) => $s->chapters->sum(fn (Chapter $c) => $c->materials->count()));

        $questions = Question::query()
            ->with(['subject', 'topic', 'options'])
            ->withCount('sections')
            ->whereHas('subject', fn ($query) => $query->where('category_id', $this->category->id))
            ->when($this->subjectFilter !== '', fn ($query) => $query->where('subject_id', $this->subjectFilter))
            ->when($this->questionStatus !== '', fn ($query) => $query->where('status', $this->questionStatus))
            ->when($this->questionType !== '', fn ($query) => $query->where('type', $this->questionType))
            ->when($this->questionSearch !== '', fn ($query) => $query->where('body', 'like', '%'.$this->questionSearch.'%'))
            ->latest('updated_at')
            ->paginate(15);

        $totalQuestionsCount = Question::query()
            ->whereHas('subject', fn ($query) => $query->where('category_id', $this->category->id))
            ->count();

        $packages = ExamPackage::query()
            ->where('category_id', $this->category->id)
            ->where('is_practice', false)
            ->with(['sections' => fn ($query) => $query->withCount('questions')])
            ->withCount('attempts')
            ->latest('updated_at')
            ->get();

        return view('livewire.admin.courses.show', [
            'allCourses' => Category::query()->ordered()->get(['id', 'name', 'slug']),
            'materialsCount' => $materialsCount,
            'totalQuestionsCount' => $totalQuestionsCount,
            'questions' => $questions,
            'packages' => $packages,
            'statuses' => ContentStatus::cases(),
            'types' => QuestionType::cases(),
        ])->title('Kursus '.$this->category->name);
    }
}
