<?php

namespace App\Livewire\Billing;

use App\Models\Order;
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

    public function mount(Order $order): void
    {
        abort_unless($order->user_id === auth()->id(), 403);

        $this->order = $order->load(['plan', 'voucher', 'subscription']);
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

    public function render(PaymentGateway $gateway): View
    {
        return view('livewire.billing.order-show', [
            'instructions' => $gateway->instructions($this->order),
            'methodLabel' => config('talentra.payments.methods')[$this->order->payment_method] ?? '-',
        ])->title('Pesanan '.$this->order->code);
    }
}
