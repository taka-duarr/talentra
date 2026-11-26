<?php

namespace App\Livewire\Admin\Questions;

use App\Enums\ContentStatus;
use App\Enums\QuestionType;
use App\Models\Category;
use App\Models\Question;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin')]
#[Title('Bank soal')]
class QuestionIndex extends Component
{
    use WithPagination;

    #[Url(as: 'kategori')]
    public string $category = '';

    #[Url]
    public string $status = '';

    #[Url(as: 'tipe')]
    public string $type = '';

    #[Url(as: 'cari')]
    public string $search = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function setStatus(int $id, string $status): void
    {
        Question::query()->findOrFail($id)->update(['status' => ContentStatus::from($status)]);
    }

    public function delete(int $id): void
    {
        $question = Question::query()->findOrFail($id);

        if ($question->sections()->exists()) {
            session()->flash('error', 'Soal ini dipakai di paket try out. Keluarkan dari paket dulu atau ubah statusnya menjadi draf.');

            return;
        }

        $question->delete();
        session()->flash('status', 'Soal dihapus.');
    }

    public function render(): View
    {
        $questions = Question::query()
            ->with(['subject.category', 'topic', 'options'])
            ->withCount('sections')
            ->when($this->category !== '', fn ($query) => $query->whereHas('subject', fn ($subject) => $subject->where('category_id', $this->category)))
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->type !== '', fn ($query) => $query->where('type', $this->type))
            ->when($this->search !== '', fn ($query) => $query->where('body', 'like', '%'.$this->search.'%'))
            ->latest('updated_at')
            ->paginate(20);

        return view('livewire.admin.questions.index', [
            'questions' => $questions,
            'categories' => Category::query()->ordered()->get(['id', 'name']),
            'statuses' => ContentStatus::cases(),
            'types' => QuestionType::cases(),
        ]);
    }
}
