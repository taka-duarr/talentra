<?php

namespace Tests\Feature;

use App\Enums\AttemptStatus;
use App\Livewire\Admin\Attempts\AttemptIndex;
use App\Livewire\Auth\Login;
use App\Models\AttemptAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSection;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private function submittedAttempt(User $user, float $score, bool $passed): ExamAttempt
    {
        $package = ExamPackage::factory()->create();
        $section = ExamSection::factory()->for($package, 'package')->create();
        $question = Question::factory()->withOptions()->create();
        $section->questions()->attach($question->id, ['sort_order' => 0]);

        $attempt = ExamAttempt::factory()->for($user)->for($package, 'package')->create([
            'status' => AttemptStatus::Submitted,
            'score' => $score,
            'max_score' => 100,
            'passed' => $passed,
            'correct_count' => 1,
            'submitted_at' => now(),
            'question_order' => [['q' => $question->id, 's' => $section->id]],
            'section_scores' => [['section_id' => $section->id, 'name' => $section->name, 'score' => $score, 'max_score' => 100, 'correct' => 1, 'wrong' => 0, 'blank' => 0, 'passing_score' => null, 'passed' => null]],
        ]);

        AttemptAnswer::factory()->for($attempt, 'attempt')->for($question)->create([
            'selected_option_ids' => [$question->options->firstWhere('is_correct', true)->id],
            'is_correct' => true,
            'score' => 4,
        ]);

        return $attempt;
    }

    public function test_admin_login_goes_to_the_admin_panel(): void
    {
        $admin = User::factory()->contentAdmin()->create();

        Livewire::test(Login::class)
            ->set('email', $admin->email)
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_is_redirected_away_from_the_student_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_admins_can_list_filter_and_open_attempts(): void
    {
        $rina = User::factory()->create(['name' => 'Rina Pelajar']);
        $budi = User::factory()->create(['name' => 'Budi Pelajar']);
        $passed = $this->submittedAttempt($rina, 85, true);
        $this->submittedAttempt($budi, 40, false);

        $admin = User::factory()->contentAdmin()->create();

        $this->actingAs($admin)->get(route('admin.attempts.index'))->assertOk()->assertSee('Rina Pelajar')->assertSee('Budi Pelajar');

        Livewire::actingAs($admin)->test(AttemptIndex::class)
            ->set('result', 'lolos')
            ->assertSee('Rina Pelajar')
            ->assertDontSee('Budi Pelajar')
            ->set('result', '')
            ->set('search', 'budi')
            ->assertSee('Budi Pelajar')
            ->assertDontSee('Rina Pelajar');

        $this->actingAs($admin)->get(route('admin.attempts.show', $passed))
            ->assertOk()
            ->assertSee('Rina Pelajar')
            ->assertSee('Jawaban per soal');

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Hasil terbaru')->assertSee('Rina Pelajar');
    }

    public function test_students_cannot_open_monitoring_pages(): void
    {
        $attempt = $this->submittedAttempt(User::factory()->create(), 70, true);

        $this->actingAs(User::factory()->create())->get(route('admin.attempts.index'))->assertForbidden();
        $this->get(route('admin.attempts.show', $attempt))->assertForbidden();
    }
}
