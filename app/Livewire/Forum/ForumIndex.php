<?php

namespace App\Livewire\Forum;

use App\Enums\ThreadScope;
use App\Enums\ThreadTag;
use App\Models\Category;
use App\Models\ForumThread;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Forum')]
class ForumIndex extends Component
{
    use WithPagination;

    /** "umum" or a category slug. */
    #[Url(as: 'ruang')]
    public string $room = 'umum';

    #[Url]
    public string $tag = '';

    #[Url(as: 'urut')]
    public string $sort = 'terbaru';

    #[Url(as: 'cari')]
    public string $search = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function category(): ?Category
    {
        return $this->room === 'umum' ? null : Category::query()->active()->where('slug', $this->room)->first();
    }

    public function render(): View
    {
        $threads = ForumThread::query()
            ->visible()
            ->with(['author', 'material'])
            ->when($this->category,
                fn ($query, $category) => $query->where('category_id', $category->id)->whereIn('scope', [ThreadScope::Category, ThreadScope::Material]),
                fn ($query) => $query->where('scope', ThreadScope::Global))
            ->when($this->tag !== '', fn ($query) => $query->where('tag', $this->tag))
            ->when($this->search !== '', fn ($query) => $query->where(fn ($inner) => $inner->where('title', 'like', '%'.$this->search.'%')->orWhere('body', 'like', '%'.$this->search.'%')))
            ->when($this->sort === 'belum-terjawab', fn ($query) => $query->where('replies_count', 0))
            ->orderByDesc('is_pinned')
            ->when($this->sort === 'populer', fn ($query) => $query->orderByDesc('votes_count')->orderByDesc('replies_count'))
            ->orderByDesc('last_activity_at')
            ->paginate(15);

        return view('livewire.forum.index', [
            'threads' => $threads,
            'categories' => Category::query()->active()->ordered()->get(['id', 'name', 'slug', 'color']),
            'tags' => ThreadTag::cases(),
        ]);
    }
}
