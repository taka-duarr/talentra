<?php

namespace App\Livewire\Learn;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class LearnCategory extends Component
{
    public Category $category;

    public function mount(Category $category): void
    {
        abort_unless($category->is_active, 404);

        $this->category = $category;
    }

    public function render(): View
    {
        $user = auth()->user();

        $this->category->load(['subjects.chapters.materials' => fn ($query) => $query->published()]);

        return view('livewire.learn.category', [
            'hasAccess' => $user->canAccessCategory($this->category->id),
            'completedIds' => $user->materialProgress()->pluck('material_id'),
        ])->title($this->category->name);
    }
}
