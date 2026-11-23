<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\VoucherType;
use App\Livewire\Billing\Checkout;
use App\Livewire\Billing\OrderShow;
use App\Models\Category;
use App\Models\Order;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\User;
use App\Models\Voucher;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private function planWithPrice(): array
    {
        $category = Category::factory()->create();
        $plan = Plan::factory()->create();
        $plan->categories()->attach($category);
        $price = PlanPrice::factory()->for($plan)->create(['months' => 3, 'price' => 120000]);

        return [$category, $plan, $price];
    }

    public function test_checkout_creates_pending_order_with_voucher_discount(): void
    {
        [, $plan, $price] = $this->planWithPrice();
        Voucher::factory()->create(['code' => 'HEMAT10', 'type' => VoucherType::Percent, 'value' => 10]);
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Checkout::class, ['plan' => $plan])
            ->set('price_id', $price->id)
            ->set('voucher_code', 'hemat10')
            ->call('applyVoucher')
            ->assertHasNoErrors()
            ->call('placeOrder')
            ->assertRedirect();

        $order = Order::query()->sole();
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(12000, $order->discount);
        $this->assertSame(108000, $order->total);
    }

    public function test_invalid_voucher_is_rejected(): void
    {
        [, $plan] = $this->planWithPrice();

        Livewire::actingAs(User::factory()->create())
            ->test(Checkout::class, ['plan' => $plan])
            ->set('voucher_code', 'TIDAKADA')
            ->call('applyVoucher')
            ->assertHasErrors('voucher_code');
    }

    public function test_buyer_uploads_proof_and_admin_approval_grants_access(): void
    {
        Storage::fake('local');
        [$category, , $price] = $this->planWithPrice();
        $user = User::factory()->create();
        $order = app(OrderService::class)->create($user, $price, null, 'bank_transfer');

        Livewire::actingAs($user)
            ->test(OrderShow::class, ['order' => $order])
            ->set('proof', UploadedFile::fake()->image('bukti.jpg'))
            ->call('uploadProof')
            ->assertHasNoErrors();

        $this->assertNotNull($order->refresh()->proof_path);
        $this->assertFalse($user->fresh()->canAccessCategory($category->id));

        $subscription = app(OrderService::class)->markPaid($order, User::factory()->admin()->create());

        $this->assertSame(OrderStatus::Paid, $order->refresh()->status);
        $this->assertTrue($subscription->ends_at->between(now()->addMonths(3)->subMinute(), now()->addMonths(3)->addMinute()));
        $this->assertTrue($user->fresh()->canAccessCategory($category->id));
    }

    public function test_buying_the_same_plan_again_extends_the_subscription(): void
    {
        [, , $price] = $this->planWithPrice();
        $user = User::factory()->create();
        $service = app(OrderService::class);

        $first = $service->markPaid($service->create($user, $price, null, 'qris'));
        $second = $service->markPaid($service->create($user, $price, null, 'qris'));

        $this->assertTrue($second->starts_at->equalTo($first->ends_at));
    }

    public function test_marking_paid_twice_does_not_create_two_subscriptions(): void
    {
        [, , $price] = $this->planWithPrice();
        $service = app(OrderService::class);
        $order = $service->create(User::factory()->create(), $price, null, 'qris');

        $service->markPaid($order);
        $service->markPaid($order);

        $this->assertSame(1, $order->user->subscriptions()->count());
    }

    public function test_proof_is_private_to_the_buyer(): void
    {
        Storage::fake('local');
        $order = Order::factory()->create(['proof_path' => UploadedFile::fake()->image('a.jpg')->store('payment-proofs', 'local')]);

        $this->actingAs(User::factory()->create())->get(route('orders.proof', $order))->assertForbidden();
        $this->actingAs($order->user)->get(route('orders.proof', $order))->assertOk();
    }

    public function test_overdue_orders_without_proof_expire(): void
    {
        $order = Order::factory()->create(['expires_at' => now()->subHour()]);
        $withProof = Order::factory()->create(['expires_at' => now()->subHour(), 'proof_path' => 'x.jpg']);

        app(OrderService::class)->expireOverdue();

        $this->assertSame(OrderStatus::Expired, $order->refresh()->status);
        $this->assertSame(OrderStatus::Pending, $withProof->refresh()->status);
    }
}
