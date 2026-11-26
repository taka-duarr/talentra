@php
    use App\Support\Money;
    use Illuminate\Support\Js;
@endphp
<div x-data="{ activePlan: null }" @keydown.escape.window="activePlan = null">
    <x-page-header title="Paket langganan" description="Harga per durasi. Paket nonaktif tidak tampil ke pengguna, tetapi langganan yang sudah berjalan tetap berlaku.">
        <x-slot:actions>
            <a href="{{ route('admin.plans.create') }}" wire:navigate class="btn btn-primary">+ Paket baru</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base text-[15px] [&_td]:py-4 [&_th]:py-3.5 [&_th]:text-[13px]">
                <thead>
                    <tr>
                        <th>Paket</th>
                        <th>Membuka</th>
                        <th>Daftar Harga</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plans as $plan)
                        @php
                            $planPayload = [
                                'name' => $plan->name,
                                'access' => $plan->is_all_access ? 'Semua kategori' : $plan->categories->pluck('name')->join(', '),
                                'editUrl' => route('admin.plans.edit', $plan),
                                'prices' => $plan->prices->map(fn ($price) => [
                                    'duration' => $price->durationLabel(),
                                    'price' => Money::rupiah($price->price),
                                    'compareAt' => ($price->compare_at_price && $price->compare_at_price > $price->price)
                                        ? Money::rupiah($price->compare_at_price)
                                        : null,
                                    'monthly' => Money::rupiah($price->monthlyPrice()).'/bln',
                                ])->values()->all(),
                            ];
                        @endphp
                        <tr wire:key="plan-{{ $plan->id }}">
                            <td>
                                <span class="text-base font-bold text-ink">{{ $plan->name }}</span>
                                @if ($plan->description)
                                    <span class="mt-0.5 block text-sm text-muted">{{ $plan->description }}</span>
                                @endif
                            </td>
                            <td class="text-sm font-medium text-muted">
                                {{ $plan->is_all_access ? 'Semua kategori' : $plan->categories->pluck('name')->join(', ') }}
                            </td>
                            <td class="whitespace-nowrap">
                                <button
                                    type="button"
                                    x-on:click="activePlan = {{ Js::from($planPayload) }}"
                                    class="btn btn-outline btn-sm text-sm"
                                >
                                    Lihat harga ({{ $plan->prices->count() }})
                                </button>
                            </td>
                            <td>
                                <button
                                    type="button"
                                    wire:click="toggleActive({{ $plan->id }})"
                                    class="badge cursor-pointer px-3 py-1 text-sm {{ $plan->is_active ? 'bg-brand-50 text-brand-700' : 'bg-navy-50 text-muted' }}"
                                >
                                    {{ $plan->is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.plans.edit', $plan) }}" wire:navigate class="btn btn-ghost btn-sm text-sm">Ubah</a>
                                <button type="button" wire:click="delete({{ $plan->id }})" wire:confirm="Hapus paket {{ $plan->name }}?" class="btn btn-ghost btn-sm text-sm text-coral-600">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-base text-muted">Belum ada paket langganan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal daftar harga --}}
    <template x-teleport="body">
        <div
            x-cloak
            x-show="activePlan !== null"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4 backdrop-blur-xs"
            x-on:click.self="activePlan = null"
            role="dialog"
            aria-modal="true"
            aria-labelledby="price-modal-title"
        >
            <div
                x-show="activePlan !== null"
                x-transition
                class="w-full max-w-md overflow-hidden rounded-2xl border border-line bg-white shadow-2xl"
            >
                <div class="flex items-start justify-between gap-4 border-b border-line px-5 py-4">
                    <div>
                        <h2 id="price-modal-title" class="text-lg font-extrabold text-ink" x-text="activePlan?.name"></h2>
                        <p class="mt-0.5 text-sm text-muted" x-text="'Membuka: ' + (activePlan?.access ?? '')"></p>
                    </div>
                    <button
                        type="button"
                        x-on:click="activePlan = null"
                        class="grid size-8 flex-none place-items-center rounded-lg text-lg text-subtle hover:bg-navy-50 hover:text-ink"
                        aria-label="Tutup popup"
                    >
                        ×
                    </button>
                </div>

                <div class="divide-y divide-line px-5">
                    <template x-for="(item, idx) in (activePlan?.prices ?? [])" :key="idx">
                        <div class="flex items-center justify-between gap-4 py-3.5">
                            <div>
                                <p class="text-base font-bold text-ink" x-text="item.duration"></p>
                                <p class="text-sm text-muted" x-text="item.monthly"></p>
                            </div>
                            <div class="text-right">
                                <p class="text-base font-extrabold text-ink" x-text="item.price"></p>
                                <p x-show="item.compareAt" class="text-sm text-subtle line-through" x-text="item.compareAt"></p>
                            </div>
                        </div>
                    </template>
                    <p x-show="activePlan && activePlan.prices.length === 0" class="py-6 text-center text-sm text-muted">
                        Belum ada daftar harga.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-line bg-canvas/60 px-5 py-3.5">
                    <a :href="activePlan?.editUrl" wire:navigate x-on:click="activePlan = null" class="btn btn-outline btn-sm text-sm">Ubah harga</a>
                    <button type="button" x-on:click="activePlan = null" class="btn btn-primary btn-sm text-sm">Tutup</button>
                </div>
            </div>
        </div>
    </template>
</div>
