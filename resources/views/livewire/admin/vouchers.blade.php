@php use App\Support\Money; @endphp
<div>
    <x-page-header title="Voucher" description="Kode diskon untuk checkout. Kuota dihitung saat pesanan disetujui." />

    <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
        <div class="card overflow-hidden">
            <table class="table-base">
                <thead><tr><th>Kode</th><th>Diskon</th><th>Terpakai</th><th>Berlaku</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($vouchers as $voucher)
                        <tr wire:key="voucher-{{ $voucher->id }}">
                            <td class="font-mono font-bold">{{ $voucher->code }}</td>
                            <td>{{ $voucher->type === \App\Enums\VoucherType::Percent ? $voucher->value.'%' : Money::rupiah($voucher->value) }}</td>
                            <td>{{ $voucher->used_count }}{{ $voucher->quota ? ' / '.$voucher->quota : '' }}</td>
                            <td class="text-xs text-muted">{{ $voucher->starts_at?->translatedFormat('d M Y') ?? 'Kapan saja' }} – {{ $voucher->ends_at?->translatedFormat('d M Y') ?? 'tanpa batas' }}</td>
                            <td>
                                <button type="button" wire:click="toggle({{ $voucher->id }})" @class(['badge', 'bg-brand-50 text-brand-700' => $voucher->isUsable(), 'bg-navy-50 text-muted' => ! $voucher->isUsable()])>
                                    {{ $voucher->is_active ? ($voucher->isUsable() ? 'Aktif' : 'Habis/kedaluwarsa') : 'Nonaktif' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-10 text-center text-muted">Belum ada voucher.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form wire:submit="create" class="card h-fit space-y-4 p-5">
            <h2 class="font-bold">Voucher baru</h2>
            <div><label class="field-label" for="v-code">Kode</label><input id="v-code" type="text" wire:model="code" class="field uppercase" placeholder="HEMAT20">@error('code') <p class="field-error">{{ $message }}</p> @enderror</div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="field-label" for="v-type">Jenis</label>
                    <select id="v-type" wire:model.live="type" class="field">
                        @foreach ($types as $item) <option value="{{ $item->value }}">{{ $item->label() }}</option> @endforeach
                    </select>
                </div>
                <div><label class="field-label" for="v-value">{{ $type === 'percent' ? 'Persen' : 'Nominal (Rp)' }}</label><input id="v-value" type="number" min="1" wire:model="value" class="field">@error('value') <p class="field-error">{{ $message }}</p> @enderror</div>
            </div>
            <div><label class="field-label" for="v-quota">Kuota (opsional)</label><input id="v-quota" type="number" min="1" wire:model="quota" class="field" placeholder="Tanpa batas"></div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="field-label" for="v-start">Mulai</label><input id="v-start" type="date" wire:model="starts_at" class="field"></div>
                <div><label class="field-label" for="v-end">Berakhir</label><input id="v-end" type="date" wire:model="ends_at" class="field">@error('ends_at') <p class="field-error">{{ $message }}</p> @enderror</div>
            </div>
            <button type="submit" class="btn btn-primary w-full">Buat voucher</button>
        </form>
    </div>
</div>
