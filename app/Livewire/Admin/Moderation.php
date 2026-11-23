<?php

namespace App\Livewire\Admin;

use App\Models\ForumReply;
use App\Models\ForumReport;
use App\Models\ForumThread;
use App\Services\Community\ForumService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Moderasi forum')]
class Moderation extends Component
{
    #[Url]
    public string $tab = 'laporan';

    public function hide(ForumService $forum, string $type, int $id): void
    {
        $item = $this->item($type, $id);
        $forum->setHidden($item, true);
        $forum->resolveReports($item, auth()->user(), 'hidden');
        session()->flash('status', 'Tulisan disembunyikan dan laporan ditutup.');
    }

    public function restore(ForumService $forum, string $type, int $id): void
    {
        $forum->setHidden($this->item($type, $id), false);
    }

    public function dismiss(ForumService $forum, string $type, int $id): void
    {
        $forum->resolveReports($this->item($type, $id), auth()->user(), 'dismissed');
        session()->flash('status', 'Laporan diabaikan.');
    }

    public function banAuthor(string $type, int $id, int $days): void
    {
        $author = $this->item($type, $id)->author;
        abort_if($author->isAdmin(), 403, 'Admin tidak bisa dilarang.');

        $author->forceFill(['forum_banned_until' => now()->addDays(max(1, min(365, $days)))])->save();
        session()->flash('status', $author->name.' dilarang menulis di forum selama '.$days.' hari.');
    }

    private function item(string $type, int $id): ForumThread|ForumReply
    {
        return $type === 'thread' ? ForumThread::query()->findOrFail($id) : ForumReply::query()->findOrFail($id);
    }

    public function render(): View
    {
        $reports = ForumReport::query()
            ->open()
            ->with(['reporter', 'reportable' => fn ($morph) => $morph->morphWith([ForumReply::class => ['thread']])])
            ->latest()
            ->get()
            ->filter(fn (ForumReport $report) => $report->reportable !== null)
            ->groupBy(fn (ForumReport $report) => $report->reportable_type.':'.$report->reportable_id);

        $hidden = collect()
            ->merge(ForumThread::query()->whereNotNull('hidden_at')->with('author')->latest('hidden_at')->take(30)->get())
            ->merge(ForumReply::query()->whereNotNull('hidden_at')->with(['author', 'thread'])->latest('hidden_at')->take(30)->get())
            ->sortByDesc('hidden_at');

        return view('livewire.admin.moderation', [
            'reportGroups' => $reports,
            'hidden' => $hidden,
        ]);
    }
}
