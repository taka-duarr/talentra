<?php

namespace App\Livewire\Billing;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Pesanan saya')]
class OrdersIndex extends Component
{
    use WithPagination;

    public function render(): View
    {
        return view('livewire.billing.orders', [
            'orders' => auth()->user()->orders()->with('plan')->latest()->paginate(10),
        ]);
    }
}
