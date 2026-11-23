<?php

namespace App\Livewire\Forum;

use App\Enums\ThreadScope;
use App\Enums\ThreadTag;
use App\Models\Category;
use App\Services\Community\ForumService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Mulai diskusi')]
class ThreadCreate extends Component
{
    #[Url(as: 'ruang')]
    public string $room = 'umum';

    public string $title = '';

    public string $body = '';

    public string $tag = 'tanya';

    public bool $acceptGuidelines = false;

    public function save(ForumService $forum): void
    {
        $this->validate([
            'room' => ['required', 'string'],
            'title' => ['required', 'string', 'min:8', 'max:150'],
            'body' => ['required', 'string', 'min:10', 'max:5000'],
            'tag' => ['required', Rule::enum(ThreadTag::class)],
        ]);

        $user = auth()->user();

        if ($this->acceptGuidelines) {
            $forum->acceptGuidelines($user);
        }

        $category = $this->room === 'umum' ? null : Category::query()->active()->where('slug', $this->room)->firstOrFail();

        $thread = $forum->createThread(
            $user->refresh(),
            $category ? ThreadScope::Category : ThreadScope::Global,
            $category?->id,
            null,
            $this->title,
            $this->body,
            ThreadTag::from($this->tag),
        );

        $this->redirectRoute('forum.thread', $thread, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.forum.create', [
            'categories' => Category::query()->active()->ordered()->get(['id', 'name', 'slug']),
            'tags' => ThreadTag::cases(),
            'needsGuidelines' => ! auth()->user()->hasAcceptedForumGuidelines(),
        ]);
    }
}
