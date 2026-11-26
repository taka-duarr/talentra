<?php

namespace App\Livewire\Learn;

use App\Models\Category;
use App\Models\Material;
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
            ->withCount([
                'subjects',
                'examPackages' => fn ($query) => $query->listed(),
            ])
            ->get()
            ->sortByDesc(fn (Category $category) => $category->id === $user->target_category_id)
            ->values();

        $materialsByCategory = Material::query()
            ->published()
            ->select('materials.id', 'materials.is_free', 'subjects.category_id')
            ->join('chapters', 'chapters.id', '=', 'materials.chapter_id')
            ->join('subjects', 'subjects.id', '=', 'chapters.subject_id')
            ->get()
            ->groupBy('category_id');

        $completedIds = $user->materialProgress()->pluck('material_id')->flip();

        $progressByCategory = $categories->mapWithKeys(function (Category $category) use ($materialsByCategory, $completedIds) {
            $items = $materialsByCategory->get($category->id, collect());
            $total = $items->count();
            $done = $items->filter(fn ($m) => $completedIds->has($m->id))->count();

            return [$category->id => [
                'total' => $total,
                'done' => $done,
                'percent' => $total > 0 ? (int) round($done / $total * 100) : 0,
            ]];
        });

        return view('livewire.learn.index', [
            'categories' => $categories,
            'accessibleIds' => $user->accessibleCategoryIds(),
            'targetId' => $user->target_category_id,
            'progressByCategory' => $progressByCategory,
        ]);
    }
}
