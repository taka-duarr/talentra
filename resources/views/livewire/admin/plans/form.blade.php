<div class="mx-auto max-w-2xl">
    <a href="{{ route('admin.plans') }}" wire:navigate class="text-sm font-semibold text-muted hover:text-ink">← Semua paket langganan</a>
    <h1 class="mt-3 mb-6 text-2xl font-extrabold tracking-tight">{{ $plan ? 'Ubah paket langganan' : 'Paket langganan baru' }}</h1>

    <form wire:submit="save" class="space-y-5">
        <section class="card space-y-4 p-5">
            <h2 class="font-bold">Informasi paket</h2>

            <div>
                <label class="field-label" for="plan-name">Nama paket</label>
                <input id="plan-name" type="text" wire:model="form.name" class="field" placeholder="Contoh: Paket UTBK-SNBT">
                @error('form.name') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="field-label" for="plan-desc">Deskripsi singkat</label>
                <input id="plan-desc" type="text" wire:model="form.description" class="field" placeholder="Opsional, tampil di halaman pilih paket">
                @error('form.description') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm font-medium">
                <input type="checkbox" wire:model.live="form.is_all_access" class="size-4 rounded border-line text-brand-600">
                Semua Akses (membuka semua kategori)
            </label>

            @unless ($form['is_all_access'])
                <fieldset class="pt-1">
                    <legend class="field-label">Kategori yang dibuka</legend>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($categories as $category)
                            <label class="flex items-center gap-2 rounded-xl border border-line px-3 py-2 text-sm">
                                <input type="checkbox" wire:model="categoryIds" value="{{ $category->id }}" class="size-4 rounded border-line text-brand-600">
                                {{ $category->name }}
                            </label>
                        @endforeach
                    </div>
                    @error('categoryIds') <p class="field-error">{{ $message }}</p> @enderror
                </fieldset>
            @endunless
        </section>

        <section class="card space-y-4 p-5">
            <div class="flex items-center justify-between">
                <h2 class="font-bold">Harga per durasi</h2>
                <button type="button" wire:click="addPrice" class="btn btn-outline btn-sm">+ Tambah durasi</button>
            </div>

            <div class="space-y-3">
                <div class="hidden grid-cols-[100px_1fr_1fr_32px] gap-2 text-xs font-semibold text-subtle sm:grid">
                    <span>Durasi (bln)</span>
                    <span>Harga (Rp)</span>
                    <span>Harga coret</span>
                    <span></span>
                </div>

                @foreach ($prices as $index => $price)
                    <div wire:key="price-{{ $index }}" class="space-y-1">
                        <div class="grid grid-cols-[90px_1fr_1fr_32px] items-center gap-2 sm:grid-cols-[100px_1fr_1fr_32px]">
                            <input type="number" min="1" max="36" wire:model="prices.{{ $index }}.months" class="field px-2.5 py-2" placeholder="Bulan" aria-label="Durasi bulan">
                            <input type="number" min="0" wire:model="prices.{{ $index }}.price" class="field px-2.5 py-2" placeholder="Harga" aria-label="Harga">
                            <input type="number" min="0" wire:model="prices.{{ $index }}.compare_at_price" class="field px-2.5 py-2" placeholder="Opsional" aria-label="Harga coret">
                            @if (count($prices) > 1)
                                <button type="button" wire:click="removePrice({{ $index }})" class="grid size-8 place-items-center rounded-lg text-subtle hover:bg-coral-100/60 hover:text-coral-600" aria-label="Hapus durasi">×</button>
                            @else
                                <span></span>
                            @endif
                        </div>
                        @error('prices.'.$index.'.months') <p class="field-error">{{ $message }}</p> @enderror
                        @error('prices.'.$index.'.price') <p class="field-error">{{ $message }}</p> @enderror
                        @error('prices.'.$index.'.compare_at_price') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>
        </section>

        <section class="card flex flex-wrap items-center justify-between gap-4 p-5">
            <div class="flex flex-wrap items-center gap-5">
                <label class="flex items-center gap-2 text-sm font-medium">
                    <input type="checkbox" wire:model="form.is_active" class="size-4 rounded border-line text-brand-600">
                    Paket aktif
                </label>
                <label class="flex items-center gap-2 text-sm text-muted">
                    Urutan
                    <input type="number" min="0" wire:model="form.sort_order" class="field w-20 py-1.5">
                </label>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.plans') }}" wire:navigate class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">Simpan paket</button>
            </div>
        </section>
    </form>
</div>
