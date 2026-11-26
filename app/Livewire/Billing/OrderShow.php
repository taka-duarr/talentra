<?php

namespace App\Livewire\Billing;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Payments\MidtransPaymentGateway;
use App\Payments\PaymentGateway;
use App\Services\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

#[Layout('layouts::app')]
class OrderShow extends Component
{
    use WithFileUploads;

    public Order $order;

    #[Validate('required|file|mimes:jpg,jpeg,png,webp,pdf|max:4096', as: 'bukti pembayaran')]
    public $proof;

    public string $channel = '';

    public ?string $notice = null;

    public ?string $lastCheckedAt = null;

    public bool $justPaid = false;

    /**
     * Whether the order was still unpaid at the previous render. The bound model is
     * re-read from the database on every request, so it cannot tell us this itself.
     */
    public bool $awaitingPayment = false;

    /**
     * Minimum seconds between two status requests to Midtrans for the same order.
     */
    private const REMOTE_CHECK_SECONDS = 8;

    public function mount(Order $order, PaymentGateway $gateway, OrderService $orders): void
    {
        abort_unless($order->user_id === auth()->id(), 403);

        $this->order = $order->load(['plan', 'voucher', 'subscription']);

        if ($gateway instanceof MidtransPaymentGateway) {
            $this->channel = $gateway->currentCharge($this->order)['channel']
                ?? $gateway->preferredChannels($this->order->payment_method)[0];

            $this->syncMidtransStatus($gateway, $orders);
        }
    }

    /**
     * Charges the order through the channel the buyer picked.
     */
    public function pay(PaymentGateway $gateway): void
    {
        abort_unless($this->order->isPending() && $gateway instanceof MidtransPaymentGateway, 403);

        $this->validate(['channel' => ['required', 'in:'.implode(',', array_keys(MidtransPaymentGateway::CHANNELS))]], [
            'channel.required' => 'Pilih metode pembayaran dulu.',
        ]);

        if ($gateway->currentCharge($this->order) !== null) {
            return;
        }

        try {
            $gateway->charge($this->order, $this->channel);
        } catch (RuntimeException $exception) {
            $this->addError('channel', $exception->getMessage());
        }

        $this->order->refresh();
    }

    /**
     * Drops the active charge so another channel can be chosen.
     */
    public function changeMethod(PaymentGateway $gateway): void
    {
        abort_unless($this->order->isPending() && $gateway instanceof MidtransPaymentGateway, 403);

        $gateway->resetCharge($this->order);
        $this->notice = null;
        $this->order->refresh();
    }

    public function uploadProof(): void
    {
        abort_unless($this->order->isPending(), 403);

        $this->validate();

        $path = $this->proof->store('payment-proofs', 'local');

        $this->order->forceFill([
            'proof_path' => $path,
            'proof_uploaded_at' => now(),
        ])->save();

        $this->reset('proof');
        session()->flash('status', 'Bukti pembayaran terkirim. Admin akan memverifikasi pesananmu.');
        $this->redirectRoute('orders.show', $this->order, navigate: true);
    }

    public function cancel(OrderService $orders, PaymentGateway $gateway): void
    {
        if ($gateway instanceof MidtransPaymentGateway && $this->order->isPending()) {
            $gateway->resetCharge($this->order);
        }

        $orders->cancel($this->order);
        $this->order->refresh();
    }

    /**
     * Manual "cek status" button.
     */
    public function checkPaymentStatus(PaymentGateway $gateway, OrderService $orders): void
    {
        if (! $this->order->isPending() || ! $gateway instanceof MidtransPaymentGateway) {
            return;
        }

        $this->syncMidtransStatus($gateway, $orders);

        $this->notice = $this->order->isPending()
            ? 'Pembayaran belum kami terima. Halaman ini diperbarui otomatis begitu pembayaran masuk.'
            : null;
    }

    /**
     * Live check while the buyer is paying. The database is read every time (a webhook may
     * already have settled the order); Midtrans itself is asked at most once per interval
     * per order, however many tabs are open.
     */
    public function pollStatus(PaymentGateway $gateway, OrderService $orders): void
    {
        $this->order->refresh()->load(['plan', 'voucher', 'subscription']);

        if ($this->order->isPending()
            && $gateway instanceof MidtransPaymentGateway
            && Cache::add('midtrans-status:'.$this->order->id, true, self::REMOTE_CHECK_SECONDS)) {
            $this->syncMidtransStatus($gateway, $orders);
        }

        $this->lastCheckedAt = now()->setTimezone('Asia/Jakarta')->format('H:i:s');
        $this->justPaid = $this->justPaid || ($this->awaitingPayment && $this->order->status === OrderStatus::Paid);
    }

    private function syncMidtransStatus(MidtransPaymentGateway $gateway, OrderService $orders): void
    {
        if (! $this->order->isPending() || $gateway->currentCharge($this->order) === null) {
            return;
        }

        $status = $gateway->checkStatus($this->order);
        $transactionStatus = $status['transaction_status'] ?? null;
        $fraudStatus = $status['fraud_status'] ?? 'accept';

        if ($transactionStatus === 'settlement' || ($transactionStatus === 'capture' && $fraudStatus === 'accept')) {
            $orders->markPaid($this->order);
            $this->justPaid = true;
        } elseif ($transactionStatus === 'expire') {
            $this->order->update(['status' => OrderStatus::Expired]);
        }

        $this->order->refresh()->load(['plan', 'voucher', 'subscription']);
    }

    public function dehydrate(): void
    {
        $this->awaitingPayment = $this->order->isPending();
    }

    public function render(PaymentGateway $gateway): View
    {
        return view('livewire.billing.order-show', [
            'instructions' => $gateway->instructions($this->order),
            'requiresProof' => $gateway->requiresProof(),
            'methodLabel' => config('talentra.payments.methods')[$this->order->payment_method] ?? '-',
        ])->title('Pesanan '.$this->order->code);
    }
}
