<?php

namespace App\Livewire\Forum;

use App\Models\ForumReply;
use App\Models\ForumThread;
use App\Models\ForumVote;
use App\Services\Community\ForumService;
use App\Services\Community\RankingService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class ThreadShow extends Component
{
    public ForumThread $thread;

    public string $body = '';

    public ?int $replyingTo = null;

    public bool $acceptGuidelines = false;

    /** @var array{type: string, id: int}|null */
    public ?array $reporting = null;

    public string $reportReason = '';

    public function mount(ForumThread $thread): void
    {
        abort_unless($thread->isReadableBy(auth()->user()), $thread->isHidden() ? 404 : 403);

        $this->thread = $thread;
    }

    public function reply(ForumService $forum): void
    {
        $this->validate(['body' => ['required', 'string', 'min:2', 'max:5000']]);

        if ($this->acceptGuidelines) {
            $forum->acceptGuidelines(auth()->user());
        }

        $forum->reply(auth()->user()->refresh(), $this->thread, $this->body, $this->replyingTo);

        $this->reset('body', 'replyingTo');
        $this->thread->refresh();
    }

    public function vote(ForumService $forum, string $type, int $id): void
    {
        $forum->toggleVote(auth()->user(), $this->target($type, $id));
        $this->thread->refresh();
    }

    public function markBest(ForumService $forum, int $replyId): void
    {
        $forum->markBest(auth()->user(), $this->thread, $this->thread->replies()->findOrFail($replyId));
        $this->thread->refresh();
    }

    public function startReport(string $type, int $id): void
    {
        $this->target($type, $id);
        $this->reporting = ['type' => $type, 'id' => $id];
        $this->reportReason = '';
    }

    public function submitReport(ForumService $forum): void
    {
        if ($this->reporting === null) {
            return;
        }

        $forum->report(auth()->user(), $this->target($this->reporting['type'], $this->reporting['id']), $this->reportReason);
        $this->reporting = null;
        session()->flash('status', 'Terima kasih, laporanmu sudah dikirim ke admin.');
    }

    public function toggleLock(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $this->thread->forceFill(['is_locked' => ! $this->thread->is_locked])->save();
    }

    public function togglePin(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $this->thread->forceFill(['is_pinned' => ! $this->thread->is_pinned])->save();
    }

    private function target(string $type, int $id): ForumThread|ForumReply
    {
        return $type === 'thread'
            ? $this->thread
            : $this->thread->replies()->visible()->findOrFail($id);
    }

    public function render(RankingService $ranking): View
    {
        $user = auth()->user();
        $replies = $this->thread->replies()->visible()->whereNull('parent_id')
            ->with(['author', 'children' => fn ($query) => $query->visible()->with('author')])
            ->get();

        $replyIds = $replies->pluck('id')->merge($replies->flatMap->children->pluck('id'));
        $myVotes = ForumVote::query()->where('user_id', $user->id)
            ->where(fn ($query) => $query
                ->where(fn ($inner) => $inner->where('votable_type', ForumThread::class)->where('votable_id', $this->thread->id))
                ->orWhere(fn ($inner) => $inner->where('votable_type', ForumReply::class)->whereIn('votable_id', $replyIds)))
            ->get()
            ->map(fn (ForumVote $vote) => ($vote->votable_type === ForumThread::class ? 'thread' : 'reply').':'.$vote->votable_id);

        $authors = collect([$this->thread->author])->merge($replies->pluck('author'))->merge($replies->flatMap->children->pluck('author'))->unique('id');
        $badges = $authors->mapWithKeys(fn ($author) => [$author->id => $ranking->badgesFor($author)]);

        return view('livewire.forum.thread', [
            'replies' => $replies,
            'myVotes' => $myVotes,
            'badges' => $badges,
            'canMarkBest' => $this->thread->user_id === $user->id || $user->isAdmin(),
            'needsGuidelines' => ! $user->hasAcceptedForumGuidelines(),
            'replyingToReply' => $this->replyingTo ? $this->thread->replies()->with('author')->find($this->replyingTo) : null,
        ])->title($this->thread->title);
    }
}
