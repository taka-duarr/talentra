<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Livewire\Admin\Orders\OrderShow;
use App\Livewire\Admin\Questions\QuestionForm;
use App\Models\ExamPackage;
use App\Models\Order;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_admin_pages_render_for_super_admin(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@talentra.test')->firstOrFail();
        $question = Question::query()->first();
        $order = Order::factory()->create();

        $urls = [
            route('admin.dashboard'), route('admin.categories'), route('admin.materials.index'), route('admin.materials.create'),
            route('admin.questions.index'), route('admin.questions.create'), route('admin.questions.edit', $question),
            route('admin.packages.index'), route('admin.packages.create'), route('admin.packages.edit', ExamPackage::query()->first()),
            route('admin.orders.index'), route('admin.orders.show', $order), route('admin.plans'), route('admin.vouchers'),
            route('admin.users.index'), route('admin.users.show', $admin),
        ];

        foreach ($urls as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_content_admin_cannot_open_sales_pages(): void
    {
        $contentAdmin = User::factory()->contentAdmin()->create();

        $this->actingAs($contentAdmin)->get(route('admin.questions.index'))->assertOk();
        $this->get(route('admin.orders.index'))->assertForbidden();
        $this->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_admin_can_approve_an_order(): void
    {
        $order = Order::factory()->create(['proof_path' => 'x.jpg']);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(OrderShow::class, ['order' => $order])
            ->call('approve');

        $this->assertSame(OrderStatus::Paid, $order->refresh()->status);
        $this->assertTrue($order->user->hasActiveSubscription());
    }

    public function test_question_form_requires_exactly_one_correct_answer_for_single_choice(): void
    {
        $subject = Subject::factory()->create();

        $component = Livewire::actingAs(User::factory()->contentAdmin()->create())
            ->test(QuestionForm::class)
            ->set('subject_id', $subject->id)
            ->set('body', '<p>2 + 2 = ?</p>')
            ->set('options', [
                ['body' => '4', 'is_correct' => false, 'weight' => 0],
                ['body' => '5', 'is_correct' => false, 'weight' => 0],
            ])
            ->call('save')
            ->assertHasErrors('options');

        $component->set('options.0.is_correct', true)->call('save')->assertHasNoErrors();

        $question = Question::query()->sole();
        $this->assertSame(2, $question->options()->count());
        $this->assertTrue($question->options()->first()->is_correct);
    }
}
