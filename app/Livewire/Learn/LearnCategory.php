<?php

namespace App\Livewire\Learn;

use App\Models\Category;
use App\Models\ExamPackage;
use App\Models\MaterialProgress;
use App\Services\Community\PointService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts::app')]
class LearnCategory extends Component
{
    use BuildsCoursePlayer;

    public Category $category;

    #[Url(as: 'tryout')]
    public string $tryoutSlug = '';

    public function mount(Category $category): void
    {
        abort_unless($category->is_active, 404);

        $this->category = $category;
    }

    public function markComplete(PointService $points): void
    {
        $data = $this->resolveCurrentState();
        $material = $data['activeMaterial'];
        abort_unless($material !== null && $material->isAccessibleBy(auth()->user()), 403);

        MaterialProgress::query()->firstOrCreate(
            ['user_id' => auth()->id(), 'material_id' => $material->id],
            ['completed_at' => now()],
        );

        $points->awardIfChapterCompleted(auth()->user(), $material->chapter);
    }

    public function completeAndAdvance(PointService $points): void
    {
        $data = $this->resolveCurrentState();
        $material = $data['activeMaterial'];
        abort_unless($material !== null, 404);

        $this->completeMaterialAndAdvance(auth()->user(), $material, $points);
    }

    public function markIncomplete(): void
    {
        $data = $this->resolveCurrentState();
        $material = $data['activeMaterial'];
        if (! $material) {
            return;
        }

        MaterialProgress::query()
            ->where('user_id', auth()->id())
            ->where('material_id', $material->id)
            ->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveCurrentState(): array
    {
        $activePackage = null;
        if ($this->tryoutSlug !== '') {
            $activePackage = ExamPackage::query()
                ->listed()
                ->where('category_id', $this->category->id)
                ->where('slug', $this->tryoutSlug)
                ->with(['category', 'sections' => fn ($query) => $query->withCount('questions')])
                ->first();
        }

        return $this->buildCoursePlayerData(auth()->user(), $this->category, null, $activePackage);
    }

    public function render(): View
    {
        return view('livewire.learn.category', $this->resolveCurrentState())
            ->title($this->category->name);
    }
}
