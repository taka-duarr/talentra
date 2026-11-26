<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Paket langganan')]
class Plans extends Component
{
    public function toggleActive(int $id): void
    {
        $plan = Plan::query()->findOrFail($id);
        $plan->update(['is_active' => ! $plan->is_active]);
    }

    public function delete(int $id): void
    {
        $plan = Plan::query()->findOrFail($id);

        $hasHistory = Order::query()->where('plan_id', $plan->id)->exists()
            || Subscription::query()->where('plan_id', $plan->id)->exists();

        if ($hasHistory) {
            session()->flash('error', 'Paket yang sudah pernah dibeli tidak dapat dihapus. Nonaktifkan paket saja.');

            return;
        }

        $plan->delete();
        session()->flash('status', 'Paket langganan dihapus.');
    }

    public function render(): View
    {
        return view('livewire.admin.plans', [
            'plans' => Plan::query()->with(['prices', 'categories'])->orderBy('sort_order')->get(),
        ]);
    }
}
