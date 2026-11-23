<?php

namespace App\Services\Community;

use App\Models\Chapter;
use App\Models\ExamAttempt;
use App\Models\MaterialProgress;
use App\Models\User;
use App\Models\UserPoint;

class PointService
{
    /**
     * Awards points once per (user, reason, source). Returns false if it was already awarded.
     */
    public function award(User $user, string $reason, string $sourceKey): bool
    {
        $points = (int) config("talentra.points.{$reason}", 0);

        if ($points === 0) {
            return false;
        }

        return UserPoint::query()->insertOrIgnore([
            'user_id' => $user->id,
            'points' => $points,
            'reason' => $reason,
            'source_key' => $sourceKey,
            'created_at' => now(),
        ]) > 0;
    }

    public function awardForAttempt(ExamAttempt $attempt): void
    {
        $attempt->loadMissing('package', 'user');

        // Only first attempts of ranked, non-practice packages earn points.
        if (! $attempt->is_first_attempt || ! $attempt->package->counts_for_ranking || $attempt->package->is_practice) {
            return;
        }

        $this->award($attempt->user, 'tryout_completed', 'attempt:'.$attempt->id);

        if ($attempt->passed === true) {
            $this->award($attempt->user, 'tryout_passed', 'attempt:'.$attempt->id);
        }
    }

    /**
     * Awards the chapter bonus when every published material in the chapter is completed.
     */
    public function awardIfChapterCompleted(User $user, Chapter $chapter): void
    {
        $materialIds = $chapter->materials()->published()->pluck('id');

        if ($materialIds->isEmpty()) {
            return;
        }

        $completed = MaterialProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('material_id', $materialIds)
            ->count();

        if ($completed === $materialIds->count()) {
            $this->award($user, 'chapter_completed', 'chapter:'.$chapter->id);
        }
    }
}
