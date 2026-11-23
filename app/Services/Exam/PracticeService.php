<?php

namespace App\Services\Exam;

use App\Enums\ContentStatus;
use App\Models\ExamPackage;
use App\Models\Question;
use App\Models\Topic;

/**
 * Builds the hidden "practice this topic" package behind the result page recommendations.
 */
class PracticeService
{
    public const QUESTIONS_PER_SESSION = 10;

    public const MINIMUM_QUESTIONS = 3;

    public function availableQuestionCount(Topic $topic): int
    {
        return Question::query()->published()->where('topic_id', $topic->id)->count();
    }

    public function packageFor(Topic $topic): ExamPackage
    {
        $topic->loadMissing('subject.category');

        $package = ExamPackage::query()->firstOrCreate(
            ['slug' => 'latihan-topik-'.$topic->id],
            [
                'category_id' => $topic->subject->category_id,
                'topic_id' => $topic->id,
                'title' => 'Latihan: '.$topic->name,
                'description' => 'Latihan acak '.self::QUESTIONS_PER_SESSION.' soal topik '.$topic->name.'.',
                'duration_minutes' => 20,
                'question_limit' => self::QUESTIONS_PER_SESSION,
                'is_free' => false,
                'is_practice' => true,
                'shuffle_questions' => true,
                'shuffle_options' => true,
                'counts_for_ranking' => false,
                'score_correct' => 1,
                'score_wrong' => 0,
                'score_blank' => 0,
                'status' => ContentStatus::Published,
                'published_at' => now(),
            ],
        );

        // Keep the question pool in sync with the bank, so new questions are picked up.
        $section = $package->sections()->firstOrCreate(
            ['name' => $topic->name],
            ['subject_id' => $topic->subject_id, 'sort_order' => 0],
        );

        $questionIds = Question::query()->published()->where('topic_id', $topic->id)->pluck('id');
        $section->questions()->sync($questionIds->mapWithKeys(fn ($id, $index) => [$id => ['sort_order' => $index]])->all());

        return $package;
    }
}
