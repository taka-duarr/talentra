<?php

namespace App\Livewire\Forum;

use App\Enums\ThreadScope;
use App\Enums\ThreadTag;
use App\Models\Material;
use App\Services\Community\ForumService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Discussion box embedded under each material.
 */
class MaterialDiscussion extends Component
{
    public Material $material;

    public string $title = '';

    public string $body = '';

    public bool $acceptGuidelines = false;

    public bool $showForm = false;

    public function ask(ForumService $forum): void
    {
        $this->validate([
            'title' => ['required', 'string', 'min:8', 'max:150'],
            'body' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        if ($this->acceptGuidelines) {
            $forum->acceptGuidelines(auth()->user());
        }

        $thread = $forum->createThread(auth()->user()->refresh(), ThreadScope::Material, null, $this->material, $this->title, $this->body, ThreadTag::Tanya);

        $this->redirectRoute('forum.thread', $thread, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.forum.material-discussion', [
            'threads' => $this->material->forumThreads()->visible()->with('author')->orderByDesc('is_pinned')->latest('last_activity_at')->take(10)->get(),
            'needsGuidelines' => ! auth()->user()->hasAcceptedForumGuidelines(),
        ]);
    }
}
