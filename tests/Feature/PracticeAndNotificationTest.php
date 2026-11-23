<?php

namespace Tests\Feature;

use App\Models\ExamAttempt;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Subscription;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use App\Notifications\SubscriptionExpiring;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PracticeAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function topicWithQuestions(int $count): Topic
    {
        $subject = Subject::factory()->create();
        $topic = Topic::factory()->for($subject)->create();
        Question::factory()->withOptions()->count($count)->create(['subject_id' => $subject->id, 'topic_id' => $topic->id]);

        return $topic;
    }

    public function test_subscriber_can_start_topic_practice_with_ten_random_questions(): void
    {
        $topic = $this->topicWithQuestions(14);
        $user = User::factory()->create();
        Subscription::factory()->for($user)->for(Plan::factory()->allAccess())->create();

        $this->actingAs($user)->post(route('practice.start', $topic))->assertRedirect();

        $attempt = ExamAttempt::query()->sole();
        $this->assertTrue($attempt->package->is_practice);
        $this->assertCount(10, $attempt->question_order);
        $this->assertFalse($attempt->package->counts_for_ranking);

        $this->get(route('tryout.index'))->assertDontSee('Latihan: '.$topic->name);
    }

    public function test_free_user_is_sent_to_plans_for_practice(): void
    {
        $topic = $this->topicWithQuestions(5);

        $this->actingAs(User::factory()->create())
            ->post(route('practice.start', $topic))
            ->assertRedirect(route('plans.index'));

        $this->assertSame(0, ExamAttempt::query()->count());
    }

    public function test_order_approval_and_rejection_notify_the_buyer(): void
    {
        Notification::fake();
        $service = app(OrderService::class);

        $paid = Order::factory()->create();
        $service->markPaid($paid);
        $service->markPaid($paid);
        Notification::assertSentToTimes($paid->user, OrderStatusChanged::class, 1);

        $rejected = Order::factory()->create();
        $service->reject($rejected, User::factory()->admin()->create(), 'Nominal tidak sesuai');
        Notification::assertSentTo($rejected->user, OrderStatusChanged::class);
    }

    public function test_expiring_subscriptions_get_reminders_unless_renewed(): void
    {
        Notification::fake();
        $plan = Plan::factory()->create();

        $expiring = Subscription::factory()->for($plan)->create(['ends_at' => now()->addDays(7)->setTime(12, 0)]);
        $renewed = Subscription::factory()->for($plan)->create(['ends_at' => now()->addDay()->setTime(12, 0)]);
        Subscription::factory()->for($renewed->user)->for($plan)->create(['starts_at' => $renewed->ends_at, 'ends_at' => $renewed->ends_at->copy()->addMonth()]);

        $this->artisan('subscriptions:remind')->assertSuccessful();

        Notification::assertSentTo($expiring->user, SubscriptionExpiring::class);
        Notification::assertNotSentTo($renewed->user, SubscriptionExpiring::class);
    }
}
