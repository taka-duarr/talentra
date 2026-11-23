<?php

namespace App\Livewire\Learn;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Belajar')]
class LearnIndex extends Component
{
    public function render(): View
    {
        $user = auth()->user();

        $categories = Category::query()
            ->active()
            ->ordered()
            ->withCount(['subjects', 'examPackages' => fn ($query) => $query->listed()])
            ->get()
            ->sortByDesc(fn (Category $category) => $category->id === $user->target_category_id)
            ->values();

        return view('livewire.learn.index', [
            'categories' => $categories,
            'accessibleIds' => $user->accessibleCategoryIds(),
            'targetId' => $user->target_category_id,
        ]);
    }
}
