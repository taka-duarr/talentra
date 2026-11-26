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
    use BuildsCoursePlayer;

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

    public function completeAndAdvance(PointService $points): void
    {
        $this->completeMaterialAndAdvance(auth()->user(), $this->material, $points);
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
        $category = $this->material->chapter->subject->category;
        $data = $this->buildCoursePlayerData(auth()->user(), $category, $this->material);

        return view('livewire.learn.material', $data)->title($this->material->title);
    }
}
