<?php

namespace App\Services\Community;

use App\Enums\AttemptStatus;
use App\Models\Category;
use App\Models\ExamPackage;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Leaderboards are computed with SQL aggregates and cached for a few minutes.
 * Each row: rank, user_id, name (null when the user hides from ranking), score, detail.
 */
class RankingService
{
    public const PERIODS = ['minggu' => 'Minggu ini', 'bulan' => 'Bulan ini', 'semua' => 'Sepanjang waktu'];

    /**
     * @return Collection<int, array{rank: int, user_id: int, name: string|null, score: float, detail: string}>
     */
    public function package(ExamPackage $package): Collection
    {
        return $this->remember("package:{$package->id}", function () use ($package) {
            $rows = DB::table('exam_attempts')
                ->join('users', 'users.id', '=', 'exam_attempts.user_id')
                ->where('exam_attempts.exam_package_id', $package->id)
                ->where('exam_attempts.status', AttemptStatus::Submitted->value)
                ->where('exam_attempts.is_first_attempt', true)
                ->whereNull('users.ranking_excluded_at')
                ->whereNull('users.blocked_at')
                ->orderByDesc('exam_attempts.score')
                ->orderByRaw('TIMESTAMPDIFF(SECOND, exam_attempts.started_at, exam_attempts.submitted_at)')
                ->limit(500)
                ->get(['users.id as user_id', 'users.name', 'users.display_name', 'users.hide_from_ranking', 'exam_attempts.score', 'exam_attempts.max_score',
                    DB::raw('TIMESTAMPDIFF(SECOND, exam_attempts.started_at, exam_attempts.submitted_at) as seconds')]);

            return $this->withRanks($rows, fn ($row) => (float) $row->score, fn ($row) => 'skor '.$this->number($row->score).' · '.(int) ceil($row->seconds / 60).' mnt');
        });
    }

    /**
     * Average percentage of first attempts on the category's ranked packages within the period.
     *
     * @return Collection<int, array{rank: int, user_id: int, name: string|null, score: float, detail: string}>
     */
    public function category(Category $category, string $period): Collection
    {
        return $this->remember("category:{$category->id}:{$period}", function () use ($category, $period) {
            $rows = DB::table('exam_attempts')
                ->join('exam_packages', 'exam_packages.id', '=', 'exam_attempts.exam_package_id')
                ->join('users', 'users.id', '=', 'exam_attempts.user_id')
                ->where('exam_packages.category_id', $category->id)
                ->where('exam_packages.counts_for_ranking', true)
                ->where('exam_packages.is_practice', false)
                ->where('exam_attempts.status', AttemptStatus::Submitted->value)
                ->where('exam_attempts.is_first_attempt', true)
                ->where('exam_attempts.max_score', '>', 0)
                ->whereNull('users.ranking_excluded_at')
                ->whereNull('users.blocked_at')
                ->when($this->since($period), fn ($query, $since) => $query->where('exam_attempts.submitted_at', '>=', $since))
                ->groupBy('users.id', 'users.name', 'users.display_name', 'users.hide_from_ranking')
                ->havingRaw('count(*) >= ?', [config('talentra.ranking.category_min_packages')])
                ->orderByDesc('average')
                ->limit(500)
                ->get(['users.id as user_id', 'users.name', 'users.display_name', 'users.hide_from_ranking',
                    DB::raw('avg(exam_attempts.score / exam_attempts.max_score * 100) as average'),
                    DB::raw('count(*) as packages')]);

            return $this->withRanks($rows, fn ($row) => round((float) $row->average, 1), fn ($row) => $row->packages.' try out');
        });
    }

    /**
     * Sum of activity points within the period.
     *
     * @return Collection<int, array{rank: int, user_id: int, name: string|null, score: float, detail: string}>
     */
    public function global(string $period): Collection
    {
        return $this->remember("global:{$period}", function () use ($period) {
            $rows = DB::table('user_points')
                ->join('users', 'users.id', '=', 'user_points.user_id')
                ->whereNull('users.ranking_excluded_at')
                ->whereNull('users.blocked_at')
                ->when($this->since($period), fn ($query, $since) => $query->where('user_points.created_at', '>=', $since))
                ->groupBy('users.id', 'users.name', 'users.display_name', 'users.hide_from_ranking')
                ->orderByDesc('total')
                ->limit(500)
                ->get(['users.id as user_id', 'users.name', 'users.display_name', 'users.hide_from_ranking', DB::raw('sum(user_points.points) as total')]);

            return $this->withRanks($rows, fn ($row) => (float) $row->total, fn ($row) => 'poin');
        });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $board
     * @return array{rank: int, total: int, percentile: int}|null
     */
    public function positionOf(Collection $board, User $user): ?array
    {
        $row = $board->firstWhere('user_id', $user->id);

        if ($row === null) {
            return null;
        }

        $total = $board->count();

        return [
            'rank' => $row['rank'],
            'total' => $total,
            'percentile' => $total > 1 ? (int) round(($total - $row['rank']) / ($total - 1) * 100) : 100,
        ];
    }

    /**
     * Badge labels shown on profiles and forum posts.
     *
     * @return array<int, string>
     */
    public function badgesFor(User $user): array
    {
        $badges = [];
        $weekly = $this->positionOf($this->global('minggu'), $user);

        if ($weekly && $weekly['rank'] <= 10) {
            $badges[] = 'Top 10 mingguan';
        }

        if ($user->target_category_id && ($category = Category::query()->find($user->target_category_id))) {
            $position = $this->positionOf($this->category($category, 'semua'), $user);
            if ($position && $position['rank'] <= 100) {
                $badges[] = 'Top 100 '.$category->name;
            }
        }

        return $badges;
    }

    public function forget(): void
    {
        Cache::increment('ranking:version');
    }

    private function since(string $period): ?CarbonInterface
    {
        return match ($period) {
            'minggu' => now()->startOfWeek(),
            'bulan' => now()->startOfMonth(),
            default => null,
        };
    }

    /**
     * @param  callable(): Collection<int, array<string, mixed>>  $callback
     * @return Collection<int, array<string, mixed>>
     */
    private function remember(string $key, callable $callback): Collection
    {
        $version = Cache::get('ranking:version', 0);

        // Store plain arrays: the cache refuses to unserialize objects such as Collection.
        $rows = Cache::remember(
            "ranking:{$version}:{$key}",
            now()->addMinutes(config('talentra.ranking.cache_minutes')),
            fn () => $callback()->all(),
        );

        return collect($rows);
    }

    /**
     * Competition ranking: equal scores share a rank.
     *
     * @param  Collection<int, object>  $rows
     * @return Collection<int, array{rank: int, user_id: int, name: string|null, score: float, detail: string}>
     */
    private function withRanks(Collection $rows, callable $score, callable $detail): Collection
    {
        $rank = 0;
        $previous = null;

        return $rows->values()->map(function ($row, int $index) use (&$rank, &$previous, $score, $detail) {
            $value = $score($row);
            if ($value !== $previous) {
                $rank = $index + 1;
                $previous = $value;
            }

            return [
                'rank' => $rank,
                'user_id' => (int) $row->user_id,
                'name' => $row->hide_from_ranking ? null : ($row->display_name ?: $row->name),
                'score' => $value,
                'detail' => $detail($row),
            ];
        });
    }

    private function number(float|string $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
    }
}
