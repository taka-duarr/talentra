<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\User;
use App\Payments\MidtransPaymentGateway;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MidtransPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(): Order
    {
        $category = Category::factory()->create();
        $plan = Plan::factory()->create();
        $plan->categories()->attach($category);
        $price = PlanPrice::factory()->for($plan)->create(['months' => 1, 'price' => 50000]);
        $user = User::factory()->create();

        return app(OrderService::class)->create($user, $price, null, 'qris');
    }

    public function test_midtrans_gateway_requests_snap_token(): void
    {
        config(['talentra.payments.midtrans.server_key' => 'SB-Mid-server-test']);
        config(['talentra.payments.midtrans.is_production' => false]);

        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'dummy-snap-token-123',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/dummy-snap-token-123',
            ], 201),
        ]);

        $order = $this->createOrder();
        $gateway = app(MidtransPaymentGateway::class);

        $instructions = $gateway->instructions($order);

        $this->assertSame('midtrans', $instructions['type']);
        $this->assertSame('dummy-snap-token-123', $instructions['snap_token']);
        $this->assertSame('https://app.sandbox.midtrans.com/snap/v4/redirection/dummy-snap-token-123', $instructions['redirect_url']);
        $this->assertSame('dummy-snap-token-123', $order->refresh()->gateway_reference);
    }

    public function test_midtrans_webhook_marks_order_paid_on_settlement(): void
    {
        $serverKey = 'SB-Mid-server-test';
        config(['talentra.payments.midtrans.server_key' => $serverKey]);

        $order = $this->createOrder();
        $orderId = $order->code;
        $statusCode = '200';
        $grossAmount = (string) $order->total.'.00';
        $signature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        $response = $this->postJson(route('midtrans.webhook'), [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signature,
            'transaction_status' => 'settlement',
        ]);

        $response->assertOk();
        $this->assertSame(OrderStatus::Paid, $order->refresh()->status);
        $this->assertNotNull($order->subscription);
    }

    public function test_midtrans_webhook_rejects_invalid_signature(): void
    {
        config(['talentra.payments.midtrans.server_key' => 'SB-Mid-server-test']);

        $order = $this->createOrder();

        $response = $this->postJson(route('midtrans.webhook'), [
            'order_id' => $order->code,
            'status_code' => '200',
            'gross_amount' => (string) $order->total,
            'signature_key' => 'invalid-signature-hash',
            'transaction_status' => 'settlement',
        ]);

        $response->assertStatus(403);
        $this->assertSame(OrderStatus::Pending, $order->refresh()->status);
    }

    public function test_midtrans_webhook_cancels_order_on_cancel_status(): void
    {
        $serverKey = 'SB-Mid-server-test';
        config(['talentra.payments.midtrans.server_key' => $serverKey]);

        $order = $this->createOrder();
        $orderId = $order->code;
        $statusCode = '200';
        $grossAmount = (string) $order->total;
        $signature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        $response = $this->postJson(route('midtrans.webhook'), [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signature,
            'transaction_status' => 'cancel',
        ]);

        $response->assertOk();
        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
    }
}
