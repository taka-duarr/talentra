<?php

namespace Tests\Feature;

use App\Enums\QuestionType;
use App\Models\AttemptAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSection;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Services\Exam\ExamScorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamScorerTest extends TestCase
{
    use RefreshDatabase;

    private function attemptFor(ExamPackage $package, array $questions): ExamAttempt
    {
        $section = ExamSection::factory()->for($package, 'package')->create(['passing_score' => 6]);
        $section->questions()->sync(collect($questions)->mapWithKeys(fn ($question, $index) => [$question->id => ['sort_order' => $index]])->all());

        return ExamAttempt::factory()->for($package, 'package')->create([
            'question_order' => collect($questions)->map(fn ($question) => ['q' => $question->id, 's' => $section->id])->all(),
        ]);
    }

    public function test_it_scores_correct_wrong_and_blank_answers_with_package_rules(): void
    {
        $package = ExamPackage::factory()->create(['score_correct' => 4, 'score_wrong' => -1, 'score_blank' => 0]);
        [$correct, $wrong, $blank] = Question::factory()->withOptions()->count(3)->create();
        $attempt = $this->attemptFor($package, [$correct, $wrong, $blank]);

        AttemptAnswer::factory()->for($attempt, 'attempt')->for($correct)->create(['selected_option_ids' => [$correct->options->firstWhere('is_correct', true)->id]]);
        AttemptAnswer::factory()->for($attempt, 'attempt')->for($wrong)->create(['selected_option_ids' => [$wrong->options->firstWhere('is_correct', false)->id]]);

        app(ExamScorer::class)->score($attempt);
        $attempt->refresh();

        $this->assertSame(3.0, $attempt->score);
        $this->assertSame(12.0, $attempt->max_score);
        $this->assertSame([1, 1, 1], [$attempt->correct_count, $attempt->wrong_count, $attempt->blank_count]);
        $this->assertFalse($attempt->section_scores[0]['passed']);
        $this->assertFalse($attempt->passed);
    }

    public function test_weighted_questions_award_the_option_weight(): void
    {
        $package = ExamPackage::factory()->create();
        $question = Question::factory()->weighted()->create();
        $attempt = $this->attemptFor($package, [$question]);

        AttemptAnswer::factory()->for($attempt, 'attempt')->for($question)->create([
            'selected_option_ids' => [$question->options()->where('weight', 4)->value('id')],
        ]);

        app(ExamScorer::class)->score($attempt);

        $this->assertSame(4.0, $attempt->refresh()->score);
        $this->assertSame(5.0, $attempt->max_score);
    }

    public function test_multiple_choice_requires_the_exact_set_of_correct_options(): void
    {
        $package = ExamPackage::factory()->create();
        $question = Question::factory()->create(['type' => QuestionType::MultipleChoice]);
        $options = collect([true, true, false])->map(fn ($isCorrect, $index) => QuestionOption::factory()->for($question)->create(['is_correct' => $isCorrect, 'sort_order' => $index]));
        $attempt = $this->attemptFor($package, [$question]);

        $answer = AttemptAnswer::factory()->for($attempt, 'attempt')->for($question)->create(['selected_option_ids' => [$options[0]->id]]);
        $this->assertSame('wrong', app(ExamScorer::class)->evaluate($question->load('options'), $answer, $package)[0]);

        $answer->update(['selected_option_ids' => [$options[1]->id, $options[0]->id]]);
        $this->assertSame('correct', app(ExamScorer::class)->evaluate($question, $answer->refresh(), $package)[0]);
    }

    public function test_true_false_and_short_answer_questions(): void
    {
        $package = ExamPackage::factory()->create();

        $trueFalse = Question::factory()->create(['type' => QuestionType::TrueFalse]);
        $statements = collect([true, false])->map(fn ($isCorrect) => QuestionOption::factory()->for($trueFalse)->create(['is_correct' => $isCorrect]));
        $short = Question::factory()->create(['type' => QuestionType::ShortAnswer, 'answer_text' => '2,5|2.5']);

        $attempt = $this->attemptFor($package, [$trueFalse, $short]);
        $tfAnswer = AttemptAnswer::factory()->for($attempt, 'attempt')->for($trueFalse)->create([
            'statement_answers' => [(string) $statements[0]->id => true, (string) $statements[1]->id => false],
        ]);
        $shortAnswer = AttemptAnswer::factory()->for($attempt, 'attempt')->for($short)->create(['answer_text' => ' 2.50 ']);

        $scorer = app(ExamScorer::class);
        $this->assertSame('correct', $scorer->evaluate($trueFalse->load('options'), $tfAnswer, $package)[0]);
        $this->assertSame('correct', $scorer->evaluate($short->load('options'), $shortAnswer, $package)[0]);
    }
}
