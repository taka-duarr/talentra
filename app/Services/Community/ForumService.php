<?php

namespace App\Services\Community;

use App\Enums\ThreadScope;
use App\Enums\ThreadTag;
use App\Models\ForumReply;
use App\Models\ForumReport;
use App\Models\ForumThread;
use App\Models\ForumVote;
use App\Models\Material;
use App\Models\User;
use App\Notifications\ThreadReplied;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ForumService
{
    public function __construct(private PointService $points) {}

    public function createThread(User $user, ThreadScope $scope, ?int $categoryId, ?Material $material, string $title, string $body, ThreadTag $tag): ForumThread
    {
        $this->ensureCanPost($user);
        $this->ensureClean($title.' '.$body);

        if ($scope === ThreadScope::Material && ($material === null || ! $material->isAccessibleBy($user))) {
            throw new AuthorizationException('Kamu belum bisa berdiskusi di materi ini.');
        }

        $this->hitRateLimit($user);

        return ForumThread::create([
            'user_id' => $user->id,
            'scope' => $scope,
            'category_id' => match ($scope) {
                ThreadScope::Global => null,
                ThreadScope::Category => $categoryId,
                ThreadScope::Material => $material->categoryId(),
            },
            'material_id' => $scope === ThreadScope::Material ? $material->id : null,
            'title' => trim($title),
            'body' => trim($body),
            'tag' => $tag,
            'last_activity_at' => now(),
        ]);
    }

    public function reply(User $user, ForumThread $thread, string $body, ?int $parentId = null): ForumReply
    {
        $this->ensureCanPost($user);

        if (! $thread->isReadableBy($user)) {
            throw new AuthorizationException('Diskusi ini tidak tersedia.');
        }

        if ($thread->is_locked && ! $user->isAdmin()) {
            throw ValidationException::withMessages(['body' => 'Diskusi ini sudah dikunci admin.']);
        }

        $this->ensureClean($body);

        // Replies nest one level only: replying to a child attaches to its parent.
        $parent = $parentId ? $thread->replies()->whereKey($parentId)->first() : null;
        $parentId = $parent?->parent_id ?? $parent?->id;

        $this->hitRateLimit($user);

        $reply = DB::transaction(function () use ($user, $thread, $body, $parentId) {
            $reply = $thread->replies()->create([
                'user_id' => $user->id,
                'parent_id' => $parentId,
                'body' => trim($body),
            ]);

            $thread->increment('replies_count', 1, ['last_activity_at' => now()]);

            return $reply;
        });

        $this->notifyParticipants($reply->load('thread', 'author', 'parent.author'));

        return $reply;
    }

    /**
     * Toggles the user's upvote and returns the new vote count.
     */
    public function toggleVote(User $user, ForumThread|ForumReply $votable): int
    {
        if ($votable->user_id === $user->id) {
            throw ValidationException::withMessages(['vote' => 'Kamu tidak bisa memberi suara untuk tulisanmu sendiri.']);
        }

        return DB::transaction(function () use ($user, $votable) {
            $existing = ForumVote::query()
                ->where('user_id', $user->id)
                ->where('votable_type', $votable::class)
                ->where('votable_id', $votable->id)
                ->first();

            if ($existing) {
                $existing->delete();
                $votable->decrement('votes_count');
            } else {
                ForumVote::create(['user_id' => $user->id, 'votable_type' => $votable::class, 'votable_id' => $votable->id]);
                $votable->increment('votes_count');
            }

            return (int) $votable->refresh()->votes_count;
        });
    }

    public function markBest(User $user, ForumThread $thread, ForumReply $reply): void
    {
        if ($thread->user_id !== $user->id && ! $user->isAdmin()) {
            throw new AuthorizationException('Hanya pembuat diskusi yang bisa memilih jawaban terbaik.');
        }

        if ($reply->forum_thread_id !== $thread->id) {
            throw ValidationException::withMessages(['reply' => 'Balasan tidak ditemukan di diskusi ini.']);
        }

        $isUnmark = $thread->best_reply_id === $reply->id;
        $thread->forceFill(['best_reply_id' => $isUnmark ? null : $reply->id])->save();

        if (! $isUnmark && $reply->user_id !== $thread->user_id) {
            $this->points->award($reply->author, 'best_answer', 'reply:'.$reply->id);
        }
    }

    public function report(User $user, ForumThread|ForumReply $reportable, string $reason): void
    {
        $reason = trim($reason);

        if (mb_strlen($reason) < 5) {
            throw ValidationException::withMessages(['reason' => 'Tuliskan alasan laporan (minimal 5 karakter).']);
        }

        ForumReport::query()->updateOrCreate(
            ['user_id' => $user->id, 'reportable_type' => $reportable::class, 'reportable_id' => $reportable->id],
            ['reason' => mb_substr($reason, 0, 500), 'resolved_at' => null, 'resolved_by' => null, 'resolution' => null],
        );
    }

    public function setHidden(ForumThread|ForumReply $item, bool $hidden): void
    {
        if ($item->isHidden() === $hidden) {
            return;
        }

        $item->forceFill(['hidden_at' => $hidden ? now() : null])->save();

        if ($item instanceof ForumReply) {
            $hidden
                ? ForumThread::query()->whereKey($item->forum_thread_id)->where('replies_count', '>', 0)->decrement('replies_count')
                : ForumThread::query()->whereKey($item->forum_thread_id)->increment('replies_count');
        }
    }

    public function resolveReports(ForumThread|ForumReply $item, User $admin, string $resolution): void
    {
        $item->reports()->open()->update([
            'resolved_at' => now(),
            'resolved_by' => $admin->id,
            'resolution' => $resolution,
        ]);
    }

    public function ensureCanPost(User $user): void
    {
        if ($user->isBannedFromForum()) {
            throw ValidationException::withMessages(['body' => 'Kamu dilarang menulis di forum sampai '.$user->forum_banned_until->translatedFormat('d F Y, H:i').'.']);
        }

        if (! $user->hasAcceptedForumGuidelines()) {
            throw ValidationException::withMessages(['guidelines' => 'Setujui pedoman komunitas sebelum menulis di forum.']);
        }
    }

    public function acceptGuidelines(User $user): void
    {
        if (! $user->hasAcceptedForumGuidelines()) {
            $user->forceFill(['forum_guidelines_accepted_at' => now()])->save();
        }
    }

    private function ensureClean(string $text): void
    {
        $normalized = Str::lower($text);

        foreach (config('talentra.forum.blocked_words') as $word) {
            if (preg_match('/\b'.preg_quote($word, '/').'\b/u', $normalized)) {
                throw ValidationException::withMessages(['body' => 'Tulisanmu mengandung kata yang tidak sesuai pedoman komunitas.']);
            }
        }
    }

    private function hitRateLimit(User $user): void
    {
        $key = 'forum-post:'.$user->id;
        $max = config('talentra.forum.posts_per_window');

        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw ValidationException::withMessages(['body' => 'Kamu terlalu sering mengirim tulisan. Coba lagi dalam '.ceil(RateLimiter::availableIn($key) / 60).' menit.']);
        }

        RateLimiter::hit($key, config('talentra.forum.window_minutes') * 60);
    }

    private function notifyParticipants(ForumReply $reply): void
    {
        $recipients = collect([$reply->thread->author, $reply->parent?->author])
            ->filter()
            ->unique('id')
            ->reject(fn (User $recipient) => $recipient->id === $reply->user_id);

        foreach ($recipients as $recipient) {
            $recipient->notify(new ThreadReplied($reply));
        }
    }
}
