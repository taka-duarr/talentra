<?php

namespace Tests\Feature;

use App\Enums\AttemptStatus;
use App\Models\Category;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\User;
use App\Models\UserPoint;
use App\Services\Community\PointService;
use App\Services\Community\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    private function attempt(ExamPackage $package, User $user, float $score, bool $first = true, int $minutes = 20): ExamAttempt
    {
        return ExamAttempt::factory()->for($package, 'package')->for($user)->create([
            'status' => AttemptStatus::Submitted,
            'score' => $score,
            'max_score' => 100,
            'is_first_attempt' => $first,
            'started_at' => now()->subMinutes($minutes),
            'submitted_at' => now(),
        ]);
    }

    public function test_package_ranking_uses_first_attempts_and_respects_privacy(): void
    {
        $package = ExamPackage::factory()->create();
        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob', 'hide_from_ranking' => true]);
        $cheater = User::factory()->create(['ranking_excluded_at' => now()]);

        $this->attempt($package, $alice, 70);
        $this->attempt($package, $alice, 100, first: false);
        $this->attempt($package, $bob, 80);
        $this->attempt($package, $cheater, 99);

        $board = app(RankingService::class)->package($package);

        $this->assertSame([$bob->id, $alice->id], $board->pluck('user_id')->all());
        $this->assertNull($board[0]['name']);
        $this->assertSame('Alice', $board[1]['name']);
        $this->assertSame(70.0, $board[1]['score']);
    }

    public function test_ties_share_rank_and_faster_finisher_comes_first(): void
    {
        $package = ExamPackage::factory()->create();
        [$slow, $fast] = User::factory()->count(2)->create();
        $this->attempt($package, $slow, 80, minutes: 50);
        $this->attempt($package, $fast, 80, minutes: 10);

        $board = app(RankingService::class)->package($package);

        $this->assertSame($fast->id, $board[0]['user_id']);
        $this->assertSame([1, 1], $board->pluck('rank')->all());
    }

    public function test_category_ranking_averages_percentages(): void
    {
        $category = Category::factory()->create();
        [$first, $second] = ExamPackage::factory()->count(2)->for($category)->create();
        $user = User::factory()->create();
        $this->attempt($first, $user, 60);
        $this->attempt($second, $user, 80);

        $board = app(RankingService::class)->category($category, 'semua');

        $this->assertSame(70.0, $board[0]['score']);
    }

    public function test_global_ranking_sums_points_in_period(): void
    {
        $user = User::factory()->create();
        UserPoint::factory()->for($user)->create(['points' => 10]);
        UserPoint::factory()->for($user)->create(['points' => 20, 'created_at' => now()->subMonths(2)]);

        $ranking = app(RankingService::class);

        $this->assertSame(10.0, $ranking->global('minggu')[0]['score']);
        $this->assertSame(30.0, $ranking->global('semua')[0]['score']);
    }

    public function test_points_are_awarded_once_for_first_ranked_attempts(): void
    {
        $package = ExamPackage::factory()->create();
        $user = User::factory()->create();
        $attempt = $this->attempt($package, $user, 90);
        $attempt->update(['passed' => true]);
        $retry = $this->attempt($package, $user, 95, first: false);

        $points = app(PointService::class);
        $points->awardForAttempt($attempt);
        $points->awardForAttempt($attempt);
        $points->awardForAttempt($retry);

        $expected = config('talentra.points.tryout_completed') + config('talentra.points.tryout_passed');
        $this->assertSame($expected, (int) $user->points()->sum('points'));
    }

    public function test_leaderboards_survive_the_database_cache(): void
    {
        config(['cache.default' => 'database']);
        UserPoint::factory()->create(['points' => 15]);
        $ranking = app(RankingService::class);

        $ranking->global('semua');
        $cached = $ranking->global('semua');

        $this->assertSame(15.0, $cached[0]['score']);
    }

    public function test_ranking_page_renders_for_each_type(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'siswa@talentra.test')->firstOrFail();

        foreach (['global', 'kategori', 'paket'] as $type) {
            $this->actingAs($user)->get(route('ranking.index', ['jenis' => $type]))->assertOk();
        }
    }
}
