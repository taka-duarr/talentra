<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin')]
#[Title('Pesanan')]
class OrderIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $status = 'perlu-verifikasi';

    #[Url(as: 'cari')]
    public string $search = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $orders = Order::query()
            ->with(['user', 'plan'])
            ->when($this->status === 'perlu-verifikasi', fn ($query) => $query->where('status', OrderStatus::Pending)->whereNotNull('proof_path'))
            ->when(! in_array($this->status, ['', 'perlu-verifikasi'], true), fn ($query) => $query->where('status', $this->status))
            ->when($this->search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('code', 'like', '%'.$this->search.'%')
                ->orWhereHas('user', fn ($user) => $user->where('email', 'like', '%'.$this->search.'%')->orWhere('name', 'like', '%'.$this->search.'%'))))
            ->latest()
            ->paginate(20);

        return view('livewire.admin.orders.index', [
            'orders' => $orders,
            'statuses' => OrderStatus::cases(),
        ]);
    }
}
