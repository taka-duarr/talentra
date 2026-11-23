<?php

namespace App\Livewire\Tryout;

use App\Models\Category;
use App\Models\ExamPackage;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Try Out')]
class TryoutIndex extends Component
{
    #[Url(as: 'kategori')]
    public string $category = '';

    public function mount(): void
    {
        if ($this->category === '' && auth()->user()->targetCategory) {
            $this->category = auth()->user()->targetCategory->slug;
        }
    }

    public function render(): View
    {
        $user = auth()->user();

        $packages = ExamPackage::query()
            ->listed()
            ->with(['category', 'sections' => fn ($query) => $query->withCount('questions')])
            ->when($this->category !== '', fn ($query) => $query->whereHas('category', fn ($category) => $category->where('slug', $this->category)))
            ->orderByDesc('is_free')
            ->latest('published_at')
            ->get();

        $attemptCounts = $user->examAttempts()
            ->selectRaw('exam_package_id, count(*) as total')
            ->groupBy('exam_package_id')
            ->pluck('total', 'exam_package_id');

        return view('livewire.tryout.index', [
            'categories' => Category::query()->active()->ordered()->get(['id', 'name', 'slug']),
            'packages' => $packages,
            'attemptCounts' => $attemptCounts,
            'accessibleIds' => $user->accessibleCategoryIds(),
        ]);
    }
}
