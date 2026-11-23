<?php

namespace App\Services\Analysis;

use App\Enums\AttemptStatus;
use App\Models\ExamAttempt;
use App\Services\Exam\ExamScorer;

/**
 * Builds the statistics the analysis is based on. Contains no personal data:
 * no name, email or user id is ever included.
 */
class AnalysisContextBuilder
{
    public function __construct(private ExamScorer $scorer) {}

    /**
     * @return array<string, mixed>
     */
    public function build(ExamAttempt $attempt): array
    {
        $attempt->loadMissing('package.category', 'user.targetCategory');

        $history = ExamAttempt::query()
            ->where('user_id', $attempt->user_id)
            ->where('exam_package_id', $attempt->exam_package_id)
            ->where('status', AttemptStatus::Submitted)
            ->where('id', '<', $attempt->id)
            ->orderBy('id')
            ->get()
            ->map(fn (ExamAttempt $previous) => $previous->percentage())
            ->all();

        return [
            'exam' => $attempt->package->title,
            'category' => $attempt->package->category->name,
            'target_exam' => $attempt->user->targetCategory?->name,
            'score' => $attempt->score,
            'max_score' => $attempt->max_score,
            'percentage' => $attempt->percentage(),
            'passed' => $attempt->passed,
            'correct' => $attempt->correct_count,
            'wrong' => $attempt->wrong_count,
            'blank' => $attempt->blank_count,
            'minutes_used' => $attempt->durationMinutes(),
            'minutes_allowed' => $attempt->package->duration_minutes,
            'sections' => collect($attempt->section_scores ?? [])->map(fn (array $section) => [
                'name' => $section['name'],
                'score' => $section['score'],
                'max_score' => $section['max_score'],
                'passing_score' => $section['passing_score'],
                'passed' => $section['passed'],
            ])->all(),
            'topics' => $this->scorer->topicBreakdown($attempt)->map(fn (array $topic) => [
                'topic' => $topic['topic'],
                'correct' => $topic['correct'],
                'total' => $topic['total'],
                'percentage' => $topic['percentage'],
            ])->all(),
            'previous_percentages' => $history,
        ];
    }
}
