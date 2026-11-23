<?php

namespace App\Livewire\Ranking;

use App\Models\Category;
use App\Models\ExamPackage;
use App\Services\Community\RankingService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Ranking')]
class RankingIndex extends Component
{
    #[Url(as: 'jenis')]
    public string $type = 'global';

    #[Url(as: 'periode')]
    public string $period = 'minggu';

    #[Url(as: 'kategori')]
    public string $category = '';

    #[Url(as: 'paket')]
    public string $package = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->category = $this->category ?: (string) ($user->targetCategory?->slug ?? Category::query()->ordered()->value('slug'));
        $this->package = $this->package ?: (string) ExamPackage::query()->listed()->where('counts_for_ranking', true)->latest('published_at')->value('slug');

        if (! array_key_exists($this->period, RankingService::PERIODS)) {
            $this->period = 'minggu';
        }
    }

    public function render(RankingService $ranking): View
    {
        $categories = Category::query()->active()->ordered()->get(['id', 'name', 'slug']);
        $packages = ExamPackage::query()->listed()->where('counts_for_ranking', true)->with('category')->latest('published_at')->get(['id', 'title', 'slug', 'category_id']);

        $board = match ($this->type) {
            'kategori' => ($selected = $categories->firstWhere('slug', $this->category)) ? $ranking->category($selected, $this->period) : collect(),
            'paket' => ($selected = $packages->firstWhere('slug', $this->package)) ? $ranking->package($selected) : collect(),
            default => $ranking->global($this->period),
        };

        $user = auth()->user();

        return view('livewire.ranking.index', [
            'board' => $board->take(config('talentra.ranking.page_size')),
            'position' => $ranking->positionOf($board, $user),
            'myRow' => $board->firstWhere('user_id', $user->id),
            'categories' => $categories,
            'packages' => $packages,
            'periods' => RankingService::PERIODS,
            'unit' => match ($this->type) {
                'kategori' => '% rata-rata',
                'paket' => '',
                default => 'poin',
            },
            'badges' => $ranking->badgesFor($user),
            'totalPoints' => (int) $user->points()->sum('points'),
        ]);
    }
}
