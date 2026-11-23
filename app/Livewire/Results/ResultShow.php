<?php

namespace App\Livewire\Results;

use App\Enums\AnalysisStatus;
use App\Enums\AttemptStatus;
use App\Jobs\GenerateAttemptAnalysis;
use App\Models\ExamAttempt;
use App\Models\Material;
use App\Models\Question;
use App\Services\Community\RankingService;
use App\Services\Exam\ExamScorer;
use App\Services\Exam\PracticeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class ResultShow extends Component
{
    public ExamAttempt $attempt;

    public function mount(ExamAttempt $attempt): void
    {
        abort_unless($attempt->user_id === auth()->id() || auth()->user()->isAdmin(), 403);

        if ($attempt->isInProgress()) {
            $this->redirectRoute('exam.show', $attempt);

            return;
        }

        $this->attempt = $attempt->load('package.category');
    }

    public function retryAnalysis(): void
    {
        abort_unless($this->attempt->user_id === auth()->id(), 403);

        GenerateAttemptAnalysis::dispatchSync($this->attempt->id);
    }

    public function render(ExamScorer $scorer, RankingService $ranking): View
    {
        $user = auth()->user();
        $analysis = $this->attempt->analysis()->first();

        $topics = $scorer->topicBreakdown($this->attempt);
        $firstMaterials = Material::query()
            ->published()
            ->whereIn('chapter_id', $topics->pluck('chapter_id')->filter())
            ->orderBy('sort_order')
            ->get()
            ->unique('chapter_id')
            ->keyBy('chapter_id');

        $percentile = null;
        $others = ExamAttempt::query()
            ->where('exam_package_id', $this->attempt->exam_package_id)
            ->where('status', AttemptStatus::Submitted)
            ->where('is_first_attempt', true)
            ->where('user_id', '!=', $this->attempt->user_id);
        $otherCount = (clone $others)->count();
        if ($otherCount >= 5) {
            $percentile = (int) round((clone $others)->where('score', '<', $this->attempt->score)->count() / $otherCount * 100);
        }

        $practiceTopicIds = Question::query()
            ->published()
            ->whereIn('topic_id', $topics->pluck('topic_id')->filter())
            ->selectRaw('topic_id, count(*) as total')
            ->groupBy('topic_id')
            ->havingRaw('count(*) >= ?', [PracticeService::MINIMUM_QUESTIONS])
            ->pluck('topic_id');

        $packagePosition = $this->attempt->package->counts_for_ranking && ! $this->attempt->package->is_practice
            ? $ranking->positionOf($ranking->package($this->attempt->package), $this->attempt->user)
            : null;

        return view('livewire.results.show', [
            'practiceTopicIds' => $practiceTopicIds,
            'canPractice' => $user->canAccessCategory($this->attempt->package->category_id),
            'packagePosition' => $packagePosition,
            'analysis' => $analysis,
            'analysisPending' => $analysis === null || $analysis->status === AnalysisStatus::Pending,
            'isDemoView' => $this->attempt->package->is_free && ! $user->canAccessCategory($this->attempt->package->category_id),
            'topics' => $topics,
            'firstMaterials' => $firstMaterials,
            'percentile' => $percentile,
            'masteryLevels' => config('talentra.mastery_levels'),
        ])->title('Hasil '.$this->attempt->package->title);
    }
}
