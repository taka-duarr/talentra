<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Livewire\Billing\OrderShow;
use App\Models\Category;
use App\Models\Order;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\User;
use App\Payments\MidtransPaymentGateway;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class MidtransPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(): Order
    {
        config(['talentra.payments.gateway' => 'midtrans']);

        $category = Category::factory()->create();
        $plan = Plan::factory()->create();
        $plan->categories()->attach($category);
        $price = PlanPrice::factory()->for($plan)->create(['months' => 1, 'price' => 50000]);
        $user = User::factory()->create();

        return app(OrderService::class)->create($user, $price, null, 'qris');
    }

    public function test_bank_transfer_charge_returns_virtual_account_number(): void
    {
        config(['talentra.payments.midtrans.server_key' => 'SB-Mid-server-test']);

        Http::fake([
            'api.sandbox.midtrans.com/v2/charge' => Http::response([
                'status_code' => '201',
                'transaction_id' => 'trx-1',
                'transaction_status' => 'pending',
                'va_numbers' => [['bank' => 'bca', 'va_number' => '12345678901']],
                'expiry_time' => '2026-11-27 10:00:00',
            ]),
        ]);

        $order = $this->createOrder();
        $charge = app(MidtransPaymentGateway::class)->charge($order, 'bca');

        $this->assertSame('12345678901', $charge['va_number']);
        $this->assertSame($order->code, $order->refresh()->gateway_reference);
        $this->assertSame('bca', $order->payload['charge']['channel']);

        Http::assertSent(fn ($request) => $request['payment_type'] === 'bank_transfer'
            && $request['bank_transfer']['bank'] === 'bca'
            && $request['transaction_details']['order_id'] === $order->code
            && $request['transaction_details']['gross_amount'] === (int) $order->total);
    }

    public function test_qris_charge_keeps_qr_code_url(): void
    {
        config(['talentra.payments.midtrans.server_key' => 'SB-Mid-server-test']);

        Http::fake([
            'api.sandbox.midtrans.com/v2/charge' => Http::response([
                'status_code' => '201',
                'transaction_id' => 'trx-qr',
                'actions' => [['name' => 'generate-qr-code', 'method' => 'GET', 'url' => 'https://api.sandbox.midtrans.com/v2/qris/trx-qr/qr-code']],
            ]),
        ]);

        $charge = app(MidtransPaymentGateway::class)->charge($this->createOrder(), 'qris');

        $this->assertSame('https://api.sandbox.midtrans.com/v2/qris/trx-qr/qr-code', $charge['qr_url']);
    }

    public function test_failed_charge_is_reported_without_saving(): void
    {
        config(['talentra.payments.midtrans.server_key' => 'SB-Mid-server-test']);

        Http::fake(['api.sandbox.midtrans.com/v2/charge' => Http::response(['status_code' => '402', 'status_message' => 'Payment channel is not activated.'])]);

        $order = $this->createOrder();

        Livewire::actingAs($order->user)
            ->test(OrderShow::class, ['order' => $order])
            ->set('channel', 'bni')
            ->call('pay')
            ->assertHasErrors('channel');

        $this->assertNull($order->refresh()->payload['charge'] ?? null);
    }

    public function test_buyer_can_switch_method_and_the_old_attempt_is_ignored(): void
    {
        $serverKey = 'SB-Mid-server-test';
        config(['talentra.payments.midtrans.server_key' => $serverKey]);

        Http::fake([
            'api.sandbox.midtrans.com/v2/charge' => Http::sequence()
                ->push(['status_code' => '201', 'va_numbers' => [['bank' => 'bri', 'va_number' => '111']]])
                ->push(['status_code' => '201', 'va_numbers' => [['bank' => 'bni', 'va_number' => '222']]]),
            'api.sandbox.midtrans.com/v2/*/cancel' => Http::response(['status_code' => '200', 'transaction_status' => 'cancel']),
            'api.sandbox.midtrans.com/v2/*/status' => Http::response(['status_code' => '201', 'transaction_status' => 'pending']),
        ]);

        $order = $this->createOrder();

        Livewire::actingAs($order->user)
            ->test(OrderShow::class, ['order' => $order])
            ->set('channel', 'bri')
            ->call('pay')
            ->assertSee('111')
            ->call('changeMethod')
            ->assertSee('Pilih cara bayar')
            ->set('channel', 'bni')
            ->call('pay')
            ->assertSee('222');

        $order->refresh();
        $this->assertSame($order->code.'-2', $order->gateway_reference);

        // Midtrans reports the replaced first attempt as cancelled: the order must stay open.
        $amount = (string) $order->total.'.00';
        $this->postJson(route('midtrans.webhook'), [
            'order_id' => $order->code,
            'status_code' => '200',
            'gross_amount' => $amount,
            'signature_key' => hash('sha512', $order->code.'200'.$amount.$serverKey),
            'transaction_status' => 'cancel',
        ])->assertOk();

        $this->assertSame(OrderStatus::Pending, $order->refresh()->status);

        // Settling the current attempt pays the order.
        $this->postJson(route('midtrans.webhook'), [
            'order_id' => $order->code.'-2',
            'status_code' => '200',
            'gross_amount' => $amount,
            'signature_key' => hash('sha512', $order->code.'-2'.'200'.$amount.$serverKey),
            'transaction_status' => 'settlement',
        ])->assertOk();

        $this->assertSame(OrderStatus::Paid, $order->refresh()->status);
    }

    public function test_order_page_polling_activates_paid_order(): void
    {
        config(['talentra.payments.midtrans.server_key' => 'SB-Mid-server-test']);

        Http::fake([
            'api.sandbox.midtrans.com/v2/charge' => Http::response(['status_code' => '201', 'va_numbers' => [['bank' => 'bca', 'va_number' => '999']]]),
            'api.sandbox.midtrans.com/v2/*/status' => Http::sequence()
                ->push(['status_code' => '201', 'transaction_status' => 'pending'])
                ->push(['status_code' => '200', 'transaction_status' => 'settlement']),
        ]);

        $order = $this->createOrder();

        $page = Livewire::actingAs($order->user)
            ->test(OrderShow::class, ['order' => $order])
            ->call('pay')
            ->call('pollStatus')
            ->assertSee('999');

        $this->travel(9)->seconds();

        $page->call('pollStatus')
            ->assertSet('justPaid', true)
            ->assertSee('Pembayaran baru saja diterima');

        $this->assertSame(OrderStatus::Paid, $order->refresh()->status);
    }

    public function test_polling_asks_midtrans_at_most_once_per_interval(): void
    {
        config(['talentra.payments.midtrans.server_key' => 'SB-Mid-server-test']);

        Http::fake([
            'api.sandbox.midtrans.com/v2/charge' => Http::response(['status_code' => '201', 'va_numbers' => [['bank' => 'bca', 'va_number' => '999']]]),
            'api.sandbox.midtrans.com/v2/*/status' => Http::response(['status_code' => '201', 'transaction_status' => 'pending']),
        ]);

        $order = $this->createOrder();

        Livewire::actingAs($order->user)
            ->test(OrderShow::class, ['order' => $order])
            ->call('pay')
            ->call('pollStatus')
            ->call('pollStatus')
            ->call('pollStatus')
            ->assertNotSet('lastCheckedAt', null);

        Http::assertSentCount(2);
    }

    public function test_polling_picks_up_an_order_settled_by_the_webhook(): void
    {
        config(['talentra.payments.midtrans.server_key' => 'SB-Mid-server-test']);

        Http::fake([
            'api.sandbox.midtrans.com/v2/charge' => Http::response(['status_code' => '201', 'va_numbers' => [['bank' => 'bca', 'va_number' => '999']]]),
            'api.sandbox.midtrans.com/v2/*/status' => Http::response(['status_code' => '201', 'transaction_status' => 'pending']),
        ]);

        $order = $this->createOrder();

        $page = Livewire::actingAs($order->user)
            ->test(OrderShow::class, ['order' => $order])
            ->call('pay')
            ->call('pollStatus');

        app(OrderService::class)->markPaid($order);

        $page->call('pollStatus')
            ->assertSet('justPaid', true)
            ->assertSee('Pembayaran baru saja diterima');
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
