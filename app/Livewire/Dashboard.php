<?php

namespace App\Livewire;

use App\Enums\AttemptStatus;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\Material;
use App\Models\MaterialProgress;
use App\Services\Community\RankingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Beranda')]
class Dashboard extends Component
{
    public const CHART_WIDTH = 600;

    public const CHART_HEIGHT = 190;

    /**
     * Admins work from the admin panel, not the student dashboard.
     */
    public function mount(): void
    {
        if (auth()->user()->isAdmin()) {
            $this->redirectRoute('admin.dashboard');
        }
    }

    public function render(RankingService $ranking): View
    {
        $user = auth()->user()->load('targetCategory');

        $runningAttempt = ExamAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', AttemptStatus::InProgress)
            ->where('ends_at', '>', now())
            ->with('package')
            ->latest('id')
            ->first();

        $submitted = ExamAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', AttemptStatus::Submitted)
            ->with(['package', 'analysis'])
            ->latest('submitted_at')
            ->get();

        $recentAttempts = $submitted->take(6);
        $history = $submitted->take(10)->reverse()->values();

        $answered = $submitted->sum(fn (ExamAttempt $attempt) => $attempt->correct_count + $attempt->wrong_count + $attempt->blank_count);
        $accuracy = $answered > 0 ? round($submitted->sum('correct_count') / $answered * 100) : 0;

        $completedMaterials = $user->materialProgress()->count();
        $totalMaterials = Material::query()->published()
            ->when($user->target_category_id, fn ($query, $categoryId) => $query->whereHas('chapter.subject', fn ($subject) => $subject->where('category_id', $categoryId)))
            ->count();

        $suggestedPackages = ExamPackage::query()
            ->listed()
            ->when($user->target_category_id, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->with(['category', 'sections' => fn ($query) => $query->withCount('questions')])
            ->orderByDesc('is_free')
            ->latest('published_at')
            ->take(4)
            ->get();

        $nextPackage = $suggestedPackages->first(fn (ExamPackage $package) => $package->isAccessibleBy($user) && ! $submitted->contains('exam_package_id', $package->id))
            ?? $suggestedPackages->first();

        $latestAnalysis = $submitted->first()?->analysis?->result;

        return view('livewire.dashboard', [
            'user' => $user,
            'subscription' => $user->activeSubscriptions()->first(),
            'runningAttempt' => $runningAttempt,
            'recentAttempts' => $recentAttempts,
            'suggestedPackages' => $suggestedPackages,
            'nextPackage' => $nextPackage,
            'stats' => [
                'lastScore' => $submitted->first()?->percentage() ?? 0,
                'scoreBars' => $history->map(fn (ExamAttempt $attempt) => $attempt->percentage())->take(-7)->values(),
                'accuracy' => $accuracy,
                'attempts' => $submitted->count(),
                'completedMaterials' => $completedMaterials,
                'totalMaterials' => $totalMaterials,
                'points' => (int) $user->points()->sum('points'),
                'weeklyPoints' => (int) $user->points()->where('created_at', '>=', now()->startOfWeek())->sum('points'),
            ],
            'chart' => $this->chart($history),
            'rank' => $ranking->positionOf($ranking->global('semua'), $user),
            'studyPlan' => collect($latestAnalysis['study_plan'] ?? [])->take(3)->values(),
            'calendar' => $this->calendar($user->id),
        ]);
    }

    /**
     * Smooth line chart of the last attempts, as SVG paths in a fixed viewBox.
     *
     * @param  Collection<int, ExamAttempt>  $history
     * @return array{line: string, area: string, points: array<int, array{x: float, y: float, value: float, label: string, title: string}>}|null
     */
    private function chart(Collection $history): ?array
    {
        if ($history->count() < 2) {
            return null;
        }

        $padX = 24;
        $padTop = 34;
        $padBottom = 12;
        $usableHeight = self::CHART_HEIGHT - $padTop - $padBottom;
        $step = (self::CHART_WIDTH - $padX * 2) / ($history->count() - 1);

        $points = $history->values()->map(fn (ExamAttempt $attempt, int $index) => [
            'x' => round($padX + $index * $step, 1),
            'y' => round($padTop + $usableHeight * (1 - $attempt->percentage() / 100), 1),
            'value' => $attempt->percentage(),
            'label' => $attempt->submitted_at->setTimezone('Asia/Jakarta')->translatedFormat('d M'),
            'title' => $attempt->package->title,
        ])->all();

        // Catmull-Rom spline converted to cubic Bézier segments.
        $line = 'M'.$points[0]['x'].','.$points[0]['y'];
        $count = count($points);
        for ($i = 0; $i < $count - 1; $i++) {
            $p0 = $points[max(0, $i - 1)];
            $p1 = $points[$i];
            $p2 = $points[$i + 1];
            $p3 = $points[min($count - 1, $i + 2)];
            $c1x = round($p1['x'] + ($p2['x'] - $p0['x']) / 6, 1);
            $c1y = round($p1['y'] + ($p2['y'] - $p0['y']) / 6, 1);
            $c2x = round($p2['x'] - ($p3['x'] - $p1['x']) / 6, 1);
            $c2y = round($p2['y'] - ($p3['y'] - $p1['y']) / 6, 1);
            $line .= " C{$c1x},{$c1y} {$c2x},{$c2y} {$p2['x']},{$p2['y']}";
        }

        $last = end($points);
        $area = $line.' L'.$last['x'].','.self::CHART_HEIGHT.' L'.$points[0]['x'].','.self::CHART_HEIGHT.' Z';

        return ['line' => $line, 'area' => $area, 'points' => $points];
    }

    /**
     * Current month grid with the days the user studied.
     *
     * @return array{title: string, weeks: array<int, array<int, array{day: int|null, active: bool, today: bool}>>, activeDays: int}
     */
    private function calendar(int $userId): array
    {
        $now = now()->setTimezone('Asia/Jakarta');
        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();
        $range = [$start->copy()->setTimezone(config('app.timezone')), $end->copy()->setTimezone(config('app.timezone'))];

        $toDay = fn ($date) => (int) $date->setTimezone('Asia/Jakarta')->format('j');

        $activeDays = ExamAttempt::query()->where('user_id', $userId)->whereBetween('submitted_at', $range)->pluck('submitted_at')->map($toDay)
            ->merge(MaterialProgress::query()->where('user_id', $userId)->whereBetween('completed_at', $range)->pluck('completed_at')->map($toDay))
            ->unique()
            ->flip();

        // Weeks start on Monday.
        $cells = array_fill(0, $start->dayOfWeekIso - 1, null);
        for ($day = 1; $day <= $end->day; $day++) {
            $cells[] = $day;
        }
        while (count($cells) % 7 !== 0) {
            $cells[] = null;
        }

        $weeks = collect($cells)->map(fn (?int $day) => [
            'day' => $day,
            'active' => $day !== null && $activeDays->has($day),
            'today' => $day === $now->day,
        ])->chunk(7)->map->values()->values()->all();

        return [
            'title' => $now->translatedFormat('F Y'),
            'weeks' => $weeks,
            'activeDays' => $activeDays->count(),
        ];
    }
}
