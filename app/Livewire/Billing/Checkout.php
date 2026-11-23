<?php

namespace App\Livewire\Billing;

use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Voucher;
use App\Services\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class Checkout extends Component
{
    public Plan $plan;

    public ?int $price_id = null;

    public string $payment_method = 'qris';

    public string $voucher_code = '';

    public ?int $appliedVoucherId = null;

    public function mount(Plan $plan): void
    {
        abort_unless($plan->is_active, 404);

        $this->plan = $plan->load(['prices', 'categories']);
        $this->price_id = $this->plan->prices->sortByDesc('months')->first()?->id;
    }

    #[Computed]
    public function selectedPrice(): ?PlanPrice
    {
        return $this->plan->prices->firstWhere('id', $this->price_id);
    }

    #[Computed]
    public function voucher(): ?Voucher
    {
        return $this->appliedVoucherId ? Voucher::query()->find($this->appliedVoucherId) : null;
    }

    public function applyVoucher(OrderService $orders): void
    {
        $this->resetErrorBag('voucher_code');

        if (blank($this->voucher_code)) {
            $this->appliedVoucherId = null;

            return;
        }

        $this->appliedVoucherId = $orders->findUsableVoucher($this->voucher_code)->id;
    }

    public function removeVoucher(): void
    {
        $this->appliedVoucherId = null;
        $this->voucher_code = '';
    }

    public function placeOrder(OrderService $orders): void
    {
        $this->validate([
            'price_id' => ['required', Rule::exists('plan_prices', 'id')->where('plan_id', $this->plan->id)],
            'payment_method' => ['required', Rule::in(array_keys(config('talentra.payments.methods')))],
        ]);

        try {
            $order = $orders->create(
                auth()->user(),
                $this->selectedPrice,
                $this->voucher?->code,
                $this->payment_method,
            );
        } catch (ValidationException $exception) {
            $this->appliedVoucherId = null;

            throw $exception;
        }

        $this->redirectRoute('orders.show', $order, navigate: true);
    }

    public function render(): View
    {
        $price = $this->selectedPrice;
        $discount = $price && $this->voucher ? $this->voucher->discountFor($price->price) : 0;

        return view('livewire.billing.checkout', [
            'methods' => config('talentra.payments.methods'),
            'subtotal' => $price?->price ?? 0,
            'discount' => $discount,
            'total' => ($price?->price ?? 0) - $discount,
        ])->title('Checkout '.$this->plan->name);
    }
}
