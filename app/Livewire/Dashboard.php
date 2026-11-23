<?php

namespace App\Livewire;

use App\Enums\AttemptStatus;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\Material;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Beranda')]
class Dashboard extends Component
{
    public function render(): View
    {
        $user = auth()->user()->load('targetCategory');

        $runningAttempt = ExamAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', AttemptStatus::InProgress)
            ->where('ends_at', '>', now())
            ->with('package')
            ->latest('id')
            ->first();

        $recentAttempts = ExamAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', AttemptStatus::Submitted)
            ->with(['package', 'analysis'])
            ->latest('submitted_at')
            ->take(6)
            ->get();

        $suggestedPackages = ExamPackage::query()
            ->listed()
            ->when($user->target_category_id, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->withCount('attempts')
            ->with('category')
            ->orderByDesc('is_free')
            ->latest('published_at')
            ->take(4)
            ->get();

        $completedMaterials = $user->materialProgress()->count();
        $totalMaterials = Material::query()->published()
            ->when($user->target_category_id, fn ($query, $categoryId) => $query->whereHas('chapter.subject', fn ($subject) => $subject->where('category_id', $categoryId)))
            ->count();

        $latestRecommendations = collect($recentAttempts->first()?->analysis?->result['recommendations'] ?? [])->take(3);

        return view('livewire.dashboard', [
            'user' => $user,
            'subscriptions' => $user->activeSubscriptions(),
            'runningAttempt' => $runningAttempt,
            'recentAttempts' => $recentAttempts,
            'suggestedPackages' => $suggestedPackages,
            'completedMaterials' => $completedMaterials,
            'totalMaterials' => $totalMaterials,
            'latestRecommendations' => $latestRecommendations,
            'chart' => $recentAttempts->reverse()->values()->map(fn (ExamAttempt $attempt) => [
                'label' => $attempt->submitted_at->translatedFormat('d M'),
                'value' => $attempt->percentage(),
                'title' => $attempt->package->title,
            ]),
        ]);
    }
}
