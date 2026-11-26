@php use App\Support\Money; @endphp
<div class="mx-auto max-w-4xl">
    <a href="{{ route('plans.index') }}" wire:navigate class="text-sm font-semibold text-muted hover:text-ink">← Semua paket</a>
    <h1 class="mt-3 text-2xl font-extrabold tracking-tight sm:text-3xl">Checkout {{ $plan->name }}</h1>

    <form wire:submit="placeOrder" class="mt-8 grid gap-6 lg:grid-cols-[1fr_340px]">
        <div class="space-y-6">
            <fieldset class="card p-5">
                <legend class="px-1 font-bold">1. Pilih durasi</legend>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    @foreach ($plan->prices as $price)
                        <label wire:key="price-{{ $price->id }}" class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-line p-4 transition has-checked:border-brand-500 has-checked:bg-brand-50">
                            <span class="flex items-center gap-3">
                                <input type="radio" wire:model.live="price_id" value="{{ $price->id }}" class="size-4 border-line text-brand-600 focus:ring-brand-500">
                                <span>
                                    <span class="block font-bold">{{ $price->durationLabel() }}</span>
                                    <span class="block text-xs text-subtle">{{ Money::rupiah($price->monthlyPrice()) }}/bulan</span>
                                </span>
                            </span>
                            <span class="text-right">
                                <span class="block font-extrabold">{{ Money::rupiah($price->price) }}</span>
                                @if ($price->compare_at_price && $price->compare_at_price > $price->price)
                                    <span class="block text-xs text-subtle line-through">{{ Money::rupiah($price->compare_at_price) }}</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('price_id') <p class="field-error">{{ $message }}</p> @enderror
            </fieldset>

            <fieldset class="card p-5">
                <legend class="px-1 font-bold">2. Metode pembayaran</legend>
                <div class="mt-3 space-y-2">
                    @foreach ($methods as $value => $label)
                        <label wire:key="method-{{ $value }}" class="flex cursor-pointer items-center gap-3 rounded-xl border border-line p-4 transition has-checked:border-brand-500 has-checked:bg-brand-50">
                            <input type="radio" wire:model="payment_method" value="{{ $value }}" class="size-4 border-line text-brand-600 focus:ring-brand-500">
                            <span class="font-semibold">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @if (config('talentra.payments.gateway') === 'midtrans')
                    <p class="mt-3 text-xs text-subtle">Bank atau aplikasinya bisa kamu pilih di langkah berikutnya. Pembayaran terkonfirmasi otomatis dan langganan langsung aktif.</p>
                @else
                    <p class="mt-3 text-xs text-subtle">Pembayaran dilakukan lewat transfer dan diverifikasi admin, biasanya kurang dari 1×24 jam.</p>
                @endif
            </fieldset>
        </div>

        <aside class="card h-fit p-5 lg:sticky lg:top-24">
            <h2 class="font-bold">Ringkasan</h2>
            <div class="mt-4">
                <label for="voucher" class="field-label">Kode voucher</label>
                @if ($this->voucher)
                    <div class="flex items-center justify-between rounded-xl bg-brand-50 px-3 py-2 text-sm">
                        <span class="font-bold text-brand-700">{{ $this->voucher->code }}</span>
                        <button type="button" wire:click="removeVoucher" class="text-xs font-semibold text-muted hover:text-coral-600">Hapus</button>
                    </div>
                @else
                    <div class="flex gap-2">
                        <input id="voucher" type="text" wire:model="voucher_code" class="field uppercase" placeholder="Opsional">
                        <button type="button" wire:click="applyVoucher" class="btn btn-outline">Pakai</button>
                    </div>
                @endif
                @error('voucher_code') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <dl class="mt-5 space-y-2 border-t border-line pt-4 text-sm">
                <div class="flex justify-between"><dt class="text-muted">{{ $plan->name }} · {{ $this->selectedPrice?->durationLabel() }}</dt><dd>{{ Money::rupiah($subtotal) }}</dd></div>
                @if ($discount > 0)
                    <div class="flex justify-between text-brand-700"><dt>Diskon voucher</dt><dd>−{{ Money::rupiah($discount) }}</dd></div>
                @endif
                <div class="flex justify-between border-t border-line pt-2 text-base font-extrabold"><dt>Total</dt><dd>{{ Money::rupiah($total) }}</dd></div>
            </dl>

            <button type="submit" class="btn btn-primary mt-5 w-full py-3" wire:loading.attr="disabled" wire:target="placeOrder">Buat pesanan</button>
            <p class="mt-3 text-center text-xs text-subtle">Dengan melanjutkan, kamu menyetujui syarat & ketentuan Talentra.</p>
        </aside>
    </form>
</div>
