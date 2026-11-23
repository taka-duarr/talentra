<?php

namespace App\Services\Exam;

use App\Enums\QuestionType;
use App\Models\AttemptAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ExamScorer
{
    /**
     * Scores every answer of the attempt and stores totals and per-section results on it.
     */
    public function score(ExamAttempt $attempt): ExamAttempt
    {
        $package = $attempt->package()->with('sections')->firstOrFail();
        $order = collect($attempt->question_order);
        $questions = Question::query()->with('options')->whereIn('id', $order->pluck('q'))->get()->keyBy('id');
        $answers = $attempt->answers()->get()->keyBy('question_id');

        $sections = $package->sections->mapWithKeys(fn ($section) => [$section->id => [
            'section_id' => $section->id,
            'name' => $section->name,
            'score' => 0.0,
            'max_score' => 0.0,
            'correct' => 0,
            'wrong' => 0,
            'blank' => 0,
            'passing_score' => $section->passing_score,
            'passed' => null,
        ]])->all();

        $totals = ['score' => 0.0, 'max' => 0.0, 'correct' => 0, 'wrong' => 0, 'blank' => 0];

        foreach ($order as $entry) {
            /** @var Question|null $question */
            $question = $questions->get($entry['q']);

            if ($question === null) {
                continue;
            }

            $answer = $answers->get($question->id);
            [$outcome, $points] = $this->evaluate($question, $answer, $package);
            $maxPoints = $question->maxPoints($package);

            if ($answer !== null) {
                $answer->forceFill([
                    'is_correct' => $outcome === 'blank' ? null : $outcome === 'correct',
                    'score' => $points,
                ])->save();
            }

            $sectionKey = $entry['s'];
            $totals['score'] += $points;
            $totals['max'] += $maxPoints;
            $totals[$outcome]++;

            if (isset($sections[$sectionKey])) {
                $sections[$sectionKey]['score'] += $points;
                $sections[$sectionKey]['max_score'] += $maxPoints;
                $sections[$sectionKey][$outcome]++;
            }
        }

        foreach ($sections as $key => $section) {
            if ($section['passing_score'] !== null) {
                $sections[$key]['passed'] = $section['score'] >= $section['passing_score'];
            }
        }

        $passed = $this->determinePassed($package, $totals['score'], $sections);

        $attempt->forceFill([
            'score' => $totals['score'],
            'max_score' => $totals['max'],
            'correct_count' => $totals['correct'],
            'wrong_count' => $totals['wrong'],
            'blank_count' => $totals['blank'],
            'section_scores' => array_values($sections),
            'passed' => $passed,
        ])->save();

        return $attempt;
    }

    /**
     * @return array{0: 'correct'|'wrong'|'blank', 1: float}
     */
    public function evaluate(Question $question, ?AttemptAnswer $answer, ExamPackage $package): array
    {
        if ($answer === null || $answer->isBlank()) {
            return ['blank', (float) $package->score_blank];
        }

        if ($question->type === QuestionType::Weighted) {
            $selected = $question->options->firstWhere('id', (int) collect($answer->selected_option_ids)->first());
            $weight = (float) ($selected->weight ?? 0);

            return [$weight >= (float) $question->options->max('weight') ? 'correct' : 'wrong', $weight];
        }

        $isCorrect = match ($question->type) {
            QuestionType::SingleChoice => $this->isSingleChoiceCorrect($question, $answer),
            QuestionType::MultipleChoice => $this->isMultipleChoiceCorrect($question, $answer),
            QuestionType::TrueFalse => $this->isTrueFalseCorrect($question, $answer),
            QuestionType::ShortAnswer => $this->isShortAnswerCorrect($question, $answer),
        };

        return $isCorrect
            ? ['correct', (float) $package->score_correct]
            : ['wrong', (float) $package->score_wrong];
    }

    private function isSingleChoiceCorrect(Question $question, AttemptAnswer $answer): bool
    {
        $selectedId = (int) collect($answer->selected_option_ids)->first();

        return (bool) $question->options->firstWhere('id', $selectedId)?->is_correct;
    }

    private function isMultipleChoiceCorrect(Question $question, AttemptAnswer $answer): bool
    {
        $selected = collect($answer->selected_option_ids)->map(fn ($id) => (int) $id)->sort()->values();
        $correct = $question->options->where('is_correct', true)->pluck('id')->sort()->values();

        return $selected->all() === $correct->all();
    }

    private function isTrueFalseCorrect(Question $question, AttemptAnswer $answer): bool
    {
        $given = collect($answer->statement_answers ?? []);

        return $question->options->every(function ($option) use ($given) {
            $value = $given->get((string) $option->id, $given->get($option->id));

            return $value !== null && filter_var($value, FILTER_VALIDATE_BOOLEAN) === $option->is_correct;
        });
    }

    private function isShortAnswerCorrect(Question $question, AttemptAnswer $answer): bool
    {
        $accepted = collect(explode('|', (string) $question->answer_text))
            ->map(fn (string $value) => $this->normalize($value))
            ->filter();

        return $accepted->contains($this->normalize((string) $answer->answer_text));
    }

    private function normalize(string $value): string
    {
        $value = Str::lower(trim($value));
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        // Treat 2,5 and 2.5 as the same number.
        if (preg_match('/^-?\d+([.,]\d+)?$/', $value)) {
            $value = rtrim(rtrim(str_replace(',', '.', $value), '0'), '.');
        }

        return $value;
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     */
    private function determinePassed(ExamPackage $package, float $score, array $sections): ?bool
    {
        $sectionResults = collect($sections)->pluck('passed')->filter(fn ($value) => $value !== null);

        if ($package->passing_score === null && $sectionResults->isEmpty()) {
            return null;
        }

        $passesSections = $sectionResults->every(fn (bool $passed) => $passed);
        $passesTotal = $package->passing_score === null || $score >= $package->passing_score;

        return $passesSections && $passesTotal;
    }

    /**
     * Mastery per topic for result pages and the analysis prompt.
     *
     * @return Collection<int, array{topic_id: int|null, topic: string, chapter_id: int|null, total: int, correct: int, percentage: float}>
     */
    public function topicBreakdown(ExamAttempt $attempt): Collection
    {
        $questionIds = collect($attempt->question_order)->pluck('q');
        $questions = Question::query()->with('topic')->whereIn('id', $questionIds)->get();
        $answers = $attempt->answers()->get()->keyBy('question_id');

        return $questions
            ->groupBy(fn (Question $question) => $question->topic_id ?? 0)
            ->map(function (Collection $group) use ($answers) {
                $topic = $group->first()->topic;
                $correct = $group->filter(fn (Question $question) => $answers->get($question->id)?->is_correct === true)->count();

                return [
                    'topic_id' => $topic?->id,
                    'topic' => $topic?->name ?? 'Lainnya',
                    'chapter_id' => $topic?->chapter_id,
                    'total' => $group->count(),
                    'correct' => $correct,
                    'percentage' => round($correct / max(1, $group->count()) * 100, 1),
                ];
            })
            ->sortBy('percentage')
            ->values();
    }
}
