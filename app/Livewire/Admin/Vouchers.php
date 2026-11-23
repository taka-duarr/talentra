<?php

namespace App\Livewire\Admin;

use App\Enums\VoucherType;
use App\Models\Voucher;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Voucher')]
class Vouchers extends Component
{
    public string $code = '';

    public string $type = 'percent';

    public ?int $value = null;

    public ?int $quota = null;

    public ?string $starts_at = null;

    public ?string $ends_at = null;

    public function create(): void
    {
        $this->code = Str::upper(trim($this->code));

        $validated = $this->validate([
            'code' => ['required', 'alpha_dash', 'max:30', Rule::unique('vouchers', 'code')],
            'type' => ['required', Rule::enum(VoucherType::class)],
            'value' => ['required', 'integer', 'min:1', $this->type === 'percent' ? 'max:100' : 'max:10000000'],
            'quota' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        Voucher::create([...$validated, 'is_active' => true]);

        $this->reset();
        session()->flash('status', 'Voucher dibuat.');
    }

    public function toggle(int $id): void
    {
        $voucher = Voucher::query()->findOrFail($id);
        $voucher->update(['is_active' => ! $voucher->is_active]);
    }

    public function render(): View
    {
        return view('livewire.admin.vouchers', [
            'vouchers' => Voucher::query()->latest()->get(),
            'types' => VoucherType::cases(),
        ]);
    }
}
