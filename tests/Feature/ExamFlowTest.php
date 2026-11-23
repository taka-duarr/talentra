<?php

namespace Tests\Feature;

use App\Enums\AnalysisStatus;
use App\Enums\AttemptStatus;
use App\Models\Category;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSection;
use App\Models\Plan;
use App\Models\Question;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ExamFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: ExamPackage, 1: Collection<int, Question>}
     */
    private function packageWithQuestions(bool $free = true, int $count = 3): array
    {
        $category = Category::factory()->create();
        $package = ExamPackage::factory()->for($category)->create(['is_free' => $free, 'max_attempts' => 2]);
        $section = ExamSection::factory()->for($package, 'package')->create();
        $questions = Question::factory()->withOptions()->count($count)->create();
        $section->questions()->sync($questions->mapWithKeys(fn ($question, $index) => [$question->id => ['sort_order' => $index]])->all());

        return [$package, $questions];
    }

    public function test_user_can_take_a_free_exam_end_to_end(): void
    {
        $user = User::factory()->create();
        [$package, $questions] = $this->packageWithQuestions();

        $this->actingAs($user)->post(route('exam.start', $package))->assertRedirect();
        $attempt = ExamAttempt::query()->sole();

        $this->get(route('exam.show', $attempt))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Exam/Take')
                ->has('questions', 3)
                ->missing('questions.0.options.0.is_correct'));

        $first = $questions->first();
        $this->postJson(route('exam.answer', $attempt), [
            'question_id' => $first->id,
            'selected_option_ids' => [$first->options->firstWhere('is_correct', true)->id],
        ])->assertOk()->assertJson(['saved' => true]);

        $this->postJson(route('exam.flag', $attempt), ['question_id' => $questions[1]->id, 'flagged' => true])->assertOk();

        $this->post(route('exam.submit', $attempt))->assertRedirect(route('results.show', $attempt));

        $attempt->refresh();
        $this->assertSame(AttemptStatus::Submitted, $attempt->status);
        $this->assertSame(1, $attempt->correct_count);
        $this->assertSame(2, $attempt->blank_count);
        $this->assertSame(AnalysisStatus::Completed, $attempt->analysis->status);
        $this->assertSame('rules', $attempt->analysis->source);

        $this->get(route('results.show', $attempt))->assertOk()->assertSee('Hasil try out');
        $this->get(route('results.review', $attempt))->assertOk()->assertSee('Pembahasan');
    }

    public function test_starting_twice_resumes_the_running_attempt(): void
    {
        $user = User::factory()->create();
        [$package] = $this->packageWithQuestions();

        $this->actingAs($user)->post(route('exam.start', $package));
        $this->post(route('exam.start', $package));

        $this->assertSame(1, ExamAttempt::query()->count());
    }

    public function test_paid_package_requires_subscription(): void
    {
        $user = User::factory()->create();
        [$package] = $this->packageWithQuestions(free: false);

        $this->actingAs($user)->post(route('exam.start', $package))->assertRedirect(route('plans.index'));
        $this->assertSame(0, ExamAttempt::query()->count());

        $plan = Plan::factory()->create();
        $plan->categories()->attach($package->category_id);
        Subscription::factory()->for($user)->for($plan)->create();

        $this->actingAs($user->fresh())->post(route('exam.start', $package))->assertRedirect();
        $this->assertSame(1, ExamAttempt::query()->count());
    }

    public function test_answers_are_rejected_after_time_runs_out(): void
    {
        $user = User::factory()->create();
        [$package, $questions] = $this->packageWithQuestions();
        $this->actingAs($user)->post(route('exam.start', $package));
        $attempt = ExamAttempt::query()->sole();

        $this->travel($package->duration_minutes + 1)->minutes();

        $this->postJson(route('exam.answer', $attempt), [
            'question_id' => $questions->first()->id,
            'selected_option_ids' => [$questions->first()->options->first()->id],
        ])->assertUnprocessable();
    }

    public function test_user_cannot_open_another_users_attempt(): void
    {
        [$package] = $this->packageWithQuestions();
        $owner = User::factory()->create();
        $this->actingAs($owner)->post(route('exam.start', $package));
        $attempt = ExamAttempt::query()->sole();

        $this->actingAs(User::factory()->create())->get(route('exam.show', $attempt))->assertForbidden();
        $this->postJson(route('exam.answer', $attempt), ['question_id' => 1])->assertForbidden();
    }

    public function test_attempt_limit_is_enforced(): void
    {
        $user = User::factory()->create();
        [$package] = $this->packageWithQuestions();
        $this->actingAs($user);

        foreach (range(1, 2) as $round) {
            $this->post(route('exam.start', $package));
            $this->post(route('exam.submit', ExamAttempt::query()->latest('id')->first()));
        }

        $this->post(route('exam.start', $package))->assertSessionHas('error');
        $this->assertSame(2, ExamAttempt::query()->count());
    }
}
