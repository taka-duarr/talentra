<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin')]
class OrderShow extends Component
{
    public Order $order;

    public string $note = '';

    public function mount(Order $order): void
    {
        $this->order = $order->load(['user', 'plan', 'voucher', 'subscription', 'verifier']);
        $this->note = (string) $order->admin_note;
    }

    public function approve(OrderService $orders): void
    {
        $orders->markPaid($this->order, auth()->user());
        $this->order->refresh()->load('subscription', 'verifier');
        session()->flash('status', 'Pesanan disetujui. Langganan pembeli sudah aktif.');
    }

    public function reject(OrderService $orders): void
    {
        $this->validate(['note' => ['required', 'string', 'max:500']], ['note.required' => 'Tulis alasan penolakan untuk pembeli.']);

        $orders->reject($this->order, auth()->user(), $this->note);
        $this->order->refresh()->load('verifier');
        session()->flash('status', 'Pesanan ditolak.');
    }

    public function render(): View
    {
        $isImage = $this->order->proof_path && preg_match('/\.(jpe?g|png|webp|gif)$/i', $this->order->proof_path);

        return view('livewire.admin.orders.show', ['isImage' => $isImage])->title('Pesanan '.$this->order->code);
    }
}
