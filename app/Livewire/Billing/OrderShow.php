<?php

namespace App\Livewire\Billing;

use App\Models\Order;
use App\Payments\MidtransPaymentGateway;
use App\Payments\PaymentGateway;
use App\Services\OrderService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts::app')]
class OrderShow extends Component
{
    use WithFileUploads;

    public Order $order;

    #[Validate('required|file|mimes:jpg,jpeg,png,webp,pdf|max:4096', as: 'bukti pembayaran')]
    public $proof;

    public function mount(Order $order, PaymentGateway $gateway, OrderService $orders): void
    {
        abort_unless($order->user_id === auth()->id(), 403);

        $this->order = $order->load(['plan', 'voucher', 'subscription']);

        if ($this->order->isPending() && $gateway instanceof MidtransPaymentGateway) {
            $status = $gateway->checkStatus($this->order);

            if ($status) {
                $transactionStatus = $status['transaction_status'] ?? '';
                $fraudStatus = $status['fraud_status'] ?? 'accept';

                if ($transactionStatus === 'settlement' || ($transactionStatus === 'capture' && $fraudStatus === 'accept')) {
                    $orders->markPaid($this->order);
                    $this->order->refresh();
                }
            }
        }
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

    public function cancel(OrderService $orders): void
    {
        $orders->cancel($this->order);
        $this->order->refresh();
    }

    public function checkPaymentStatus(PaymentGateway $gateway, OrderService $orders): void
    {
        if (! $this->order->isPending()) {
            return;
        }

        if ($gateway instanceof MidtransPaymentGateway) {
            $status = $gateway->checkStatus($this->order);

            if ($status) {
                $transactionStatus = $status['transaction_status'] ?? '';
                $fraudStatus = $status['fraud_status'] ?? 'accept';

                if ($transactionStatus === 'settlement' || ($transactionStatus === 'capture' && $fraudStatus === 'accept')) {
                    $orders->markPaid($this->order);
                    session()->flash('status', 'Pembayaran berhasil dikonfirmasi! Paket langganan sudah aktif.');
                    $this->order->refresh();

                    return;
                }

                if (in_array($transactionStatus, ['cancel', 'deny'], true)) {
                    $orders->cancel($this->order);
                    session()->flash('status', 'Pesanan telah dibatalkan.');
                    $this->order->refresh();

                    return;
                }
            }
        }

        session()->flash('status', 'Pembayaran belum terdeteksi. Silakan selesaikan simulasi pembayaran terlebih dahulu.');
        $this->order->refresh();
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
