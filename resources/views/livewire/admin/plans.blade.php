@php use App\Support\Money; @endphp
<div>
    <x-page-header title="Paket langganan" description="Harga per durasi. Paket nonaktif tidak tampil ke pengguna, tetapi langganan yang sudah berjalan tetap berlaku." />

    <div class="grid gap-6 lg:grid-cols-[1fr_380px]">
        <div class="card overflow-hidden">
            <table class="table-base">
                <thead><tr><th>Paket</th><th>Membuka</th><th>Harga</th><th></th></tr></thead>
                <tbody>
                    @foreach ($plans as $plan)
                        <tr wire:key="plan-{{ $plan->id }}">
                            <td class="font-semibold">{{ $plan->name }} @unless ($plan->is_active) <span class="badge bg-navy-50 text-muted">Nonaktif</span> @endunless</td>
                            <td class="text-xs text-muted">{{ $plan->is_all_access ? 'Semua kategori' : $plan->categories->pluck('name')->join(', ') }}</td>
                            <td class="text-xs">@foreach ($plan->prices as $price) <span class="block">{{ $price->durationLabel() }}: {{ Money::rupiah($price->price) }}</span> @endforeach</td>
                            <td class="text-right"><button type="button" wire:click="edit({{ $plan->id }})" class="btn btn-ghost btn-sm">Ubah</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <form wire:submit="save" class="card h-fit space-y-4 p-5">
            <div class="flex items-center justify-between">
                <h2 class="font-bold">{{ $editingId ? 'Ubah paket' : 'Paket baru' }}</h2>
                @if ($editingId) <button type="button" wire:click="resetForm" class="text-xs font-semibold text-muted">Batal</button> @endif
            </div>
            <div><label class="field-label" for="plan-name">Nama</label><input id="plan-name" type="text" wire:model="form.name" class="field">@error('form.name') <p class="field-error">{{ $message }}</p> @enderror</div>
            <div><label class="field-label" for="plan-desc">Deskripsi</label><input id="plan-desc" type="text" wire:model="form.description" class="field"></div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model.live="form.is_all_access" class="size-4 rounded border-line text-brand-600"> Semua Akses (membuka semua kategori)</label>
            @unless ($form['is_all_access'])
                <fieldset>
                    <legend class="field-label">Kategori yang dibuka</legend>
                    <div class="grid grid-cols-2 gap-1.5">
                        @foreach ($categories as $category)
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="categoryIds" value="{{ $category->id }}" class="size-4 rounded border-line text-brand-600"> {{ $category->name }}</label>
                        @endforeach
                    </div>
                    @error('categoryIds') <p class="field-error">{{ $message }}</p> @enderror
                </fieldset>
            @endunless
            <fieldset>
                <legend class="field-label">Harga per durasi</legend>
                <div class="space-y-2">
                    @foreach ($prices as $index => $price)
                        <div wire:key="price-{{ $index }}" class="grid grid-cols-[70px_1fr_1fr_auto] items-center gap-2">
                            <input type="number" min="1" wire:model="prices.{{ $index }}.months" class="field px-2 py-1.5" placeholder="Bln" aria-label="Durasi bulan">
                            <input type="number" min="0" wire:model="prices.{{ $index }}.price" class="field px-2 py-1.5" placeholder="Harga" aria-label="Harga">
                            <input type="number" min="0" wire:model="prices.{{ $index }}.compare_at_price" class="field px-2 py-1.5" placeholder="Harga coret" aria-label="Harga coret">
                            <button type="button" wire:click="removePrice({{ $index }})" class="text-subtle hover:text-coral-600" aria-label="Hapus harga">✕</button>
                        </div>
                        @error('prices.'.$index.'.months') <p class="field-error">{{ $message }}</p> @enderror
                        @error('prices.'.$index.'.price') <p class="field-error">{{ $message }}</p> @enderror
                    @endforeach
                </div>
                <button type="button" wire:click="addPrice" class="btn btn-ghost btn-sm mt-2">+ Durasi</button>
            </fieldset>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="form.is_active" class="size-4 rounded border-line text-brand-600"> Aktif</label>
                <label class="flex items-center gap-2 text-sm">Urutan <input type="number" min="0" wire:model="form.sort_order" class="field w-20 py-1.5"></label>
            </div>
            <button type="submit" class="btn btn-primary w-full">Simpan paket</button>
        </form>
    </div>
</div>
