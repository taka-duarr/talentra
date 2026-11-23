<?php

namespace App\Livewire\Admin;

use App\Enums\AttemptStatus;
use App\Models\ExamAttempt;
use App\Models\User;
use App\Services\Community\RankingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Ranking')]
class RankingManagement extends Component
{
    public string $search = '';

    public function toggleExclusion(RankingService $ranking, int $userId): void
    {
        $user = User::query()->findOrFail($userId);
        $user->forceFill(['ranking_excluded_at' => $user->isExcludedFromRanking() ? null : now()])->save();
        $ranking->forget();

        session()->flash('status', $user->isExcludedFromRanking() ? $user->name.' dikeluarkan dari ranking.' : $user->name.' dikembalikan ke ranking.');
    }

    public function refresh(RankingService $ranking): void
    {
        $ranking->forget();
        session()->flash('status', 'Cache ranking dikosongkan. Data akan dihitung ulang.');
    }

    public function render(RankingService $ranking): View
    {
        // Attempts finished suspiciously fast or with many tab switches.
        $suspicious = ExamAttempt::query()
            ->with(['user', 'package'])
            ->where('status', AttemptStatus::Submitted)
            ->where('is_first_attempt', true)
            ->whereHas('package', fn ($query) => $query->where('counts_for_ranking', true))
            ->where(fn ($query) => $query
                ->where('tab_switches', '>=', 5)
                ->orWhereRaw('TIMESTAMPDIFF(SECOND, started_at, submitted_at) < (correct_count * 10)'))
            ->latest('submitted_at')
            ->take(15)
            ->get();

        return view('livewire.admin.ranking', [
            'topWeekly' => $ranking->global('minggu')->take(10),
            'excluded' => User::query()->whereNotNull('ranking_excluded_at')->latest('ranking_excluded_at')->get(),
            'suspicious' => $suspicious,
            'searchResults' => $this->search === '' ? collect() : User::query()
                ->where(fn ($query) => $query->where('name', 'like', '%'.$this->search.'%')->orWhere('email', 'like', '%'.$this->search.'%'))
                ->take(8)
                ->get(),
            'pointsToday' => (int) DB::table('user_points')->whereDate('created_at', today())->sum('points'),
        ]);
    }
}
