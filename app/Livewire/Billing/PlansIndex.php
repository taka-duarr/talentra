<?php

namespace App\Livewire\Billing;

use App\Models\Plan;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Langganan')]
class PlansIndex extends Component
{
    public function render(): View
    {
        $plans = Plan::query()->active()->with(['prices', 'categories'])->orderBy('sort_order')->get();
        $user = auth()->user();

        return view('livewire.billing.plans', [
            'allAccess' => $plans->firstWhere('is_all_access', true),
            'categoryPlans' => $plans->where('is_all_access', false)->values(),
            'subscriptions' => $user->activeSubscriptions(),
            'targetCategoryId' => $user->target_category_id,
        ]);
    }
}
