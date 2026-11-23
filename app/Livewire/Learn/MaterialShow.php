<?php

namespace App\Livewire\Learn;

use App\Models\Material;
use App\Models\MaterialProgress;
use App\Services\Community\PointService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class MaterialShow extends Component
{
    public Material $material;

    public function mount(Material $material): void
    {
        abort_unless($material->published_at !== null && $material->published_at->isPast(), 404);

        $this->material = $material->load('chapter.subject.category');
    }

    public function markComplete(PointService $points): void
    {
        abort_unless($this->material->isAccessibleBy(auth()->user()), 403);

        MaterialProgress::query()->firstOrCreate(
            ['user_id' => auth()->id(), 'material_id' => $this->material->id],
            ['completed_at' => now()],
        );

        $points->awardIfChapterCompleted(auth()->user(), $this->material->chapter);
    }

    public function markIncomplete(): void
    {
        MaterialProgress::query()
            ->where('user_id', auth()->id())
            ->where('material_id', $this->material->id)
            ->delete();
    }

    public function render(): View
    {
        $user = auth()->user();
        $siblings = Material::query()
            ->published()
            ->whereHas('chapter', fn ($query) => $query->where('subject_id', $this->material->chapter->subject_id))
            ->join('chapters', 'chapters.id', '=', 'materials.chapter_id')
            ->orderBy('chapters.sort_order')
            ->orderBy('materials.sort_order')
            ->select('materials.*')
            ->get();

        $index = $siblings->search(fn (Material $item) => $item->id === $this->material->id);

        return view('livewire.learn.material', [
            'unlocked' => $this->material->isAccessibleBy($user),
            'completed' => MaterialProgress::query()->where('user_id', $user->id)->where('material_id', $this->material->id)->exists(),
            'previous' => $index > 0 ? $siblings[$index - 1] : null,
            'next' => $index !== false ? $siblings->get($index + 1) : null,
        ])->title($this->material->title);
    }
}
