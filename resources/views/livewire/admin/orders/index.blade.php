@php
    use App\Support\Money;
    use Illuminate\Support\Js;
@endphp
<div x-data="{ activeOrder: null }" @keydown.escape.window="activeOrder = null">
    <x-page-header title="Pesanan" description="Kelola semua pesanan langganan dan verifikasi pembayaran." />

    <div class="mb-4 flex flex-wrap gap-3">
        <select wire:model.live="status" class="field w-auto text-sm" aria-label="Filter status pesanan">
            <option value="">Semua status</option>
            <option value="perlu-verifikasi">Perlu verifikasi (ada bukti)</option>
            @foreach ($statuses as $item)
                <option value="{{ $item->value }}">{{ $item->label() }}</option>
            @endforeach
        </select>
        <input type="search" wire:model.live.debounce.400ms="search" class="field max-w-xs text-sm" placeholder="Kode, nama, atau email" aria-label="Cari pesanan">
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base text-[15px] [&_td]:py-4 [&_th]:py-3.5 [&_th]:text-[13px]">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Pembeli</th>
                        <th>Paket</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        @php
                            $orderPayload = [
                                'code' => $order->code,
                                'buyerName' => $order->user->name,
                                'buyerEmail' => $order->user->email,
                                'plan' => $order->plan->name.' · '.$order->durationLabel(),
                                'subtotal' => Money::rupiah($order->subtotal),
                                'discount' => $order->discount > 0
                                    ? '−'.Money::rupiah($order->discount).($order->voucher ? ' ('.$order->voucher->code.')' : '')
                                    : null,
                                'total' => Money::rupiah($order->total),
                                'gateway' => $order->gateway === 'midtrans' ? 'Midtrans (otomatis)' : 'Transfer manual',
                                'method' => config('talentra.payments.methods')[$order->payment_method] ?? '—',
                                'statusLabel' => $order->status->label(),
                                'statusClass' => $order->status->badgeClass(),
                                'createdAt' => $order->created_at->translatedFormat('d M Y, H:i'),
                                'paidAt' => $order->paid_at?->translatedFormat('d M Y, H:i'),
                                'subscription' => $order->subscription
                                    ? $order->subscription->starts_at->translatedFormat('d M Y').' – '.$order->subscription->ends_at->translatedFormat('d M Y')
                                    : null,
                                'showUrl' => route('admin.orders.show', $order),
                            ];
                        @endphp
                        <tr wire:key="order-{{ $order->id }}">
                            <td class="font-mono text-sm font-semibold whitespace-nowrap text-ink">{{ $order->code }}</td>
                            <td>
                                <span class="text-base font-bold text-ink">{{ $order->user->name }}</span>
                                <span class="mt-0.5 block text-sm text-muted">{{ $order->user->email }}</span>
                            </td>
                            <td>
                                <span class="text-sm font-semibold text-ink">{{ $order->plan->name }}</span>
                                <span class="mt-0.5 block text-sm text-muted">{{ $order->durationLabel() }}</span>
                            </td>
                            <td class="text-base font-bold whitespace-nowrap text-ink">{{ Money::rupiah($order->total) }}</td>
                            <td class="whitespace-nowrap">
                                <span class="badge px-3 py-1 text-sm {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
                                @if ($order->isPending() && $order->proof_path)
                                    <span class="badge ml-1 bg-navy-600 px-2.5 py-1 text-xs text-white">Ada bukti</span>
                                @endif
                            </td>
                            <td class="text-sm whitespace-nowrap text-muted">{{ $order->created_at->translatedFormat('d M Y H:i') }}</td>
                            <td class="text-right whitespace-nowrap">
                                <button
                                    type="button"
                                    x-on:click="activeOrder = {{ Js::from($orderPayload) }}"
                                    class="btn btn-outline btn-sm text-sm"
                                >
                                    Detail
                                </button>
                                <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="btn btn-ghost btn-sm text-sm">Periksa</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-base text-muted">Tidak ada pesanan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>

    {{-- Modal detail pesanan --}}
    <template x-teleport="body">
        <div
            x-cloak
            x-show="activeOrder !== null"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4 backdrop-blur-xs"
            x-on:click.self="activeOrder = null"
            role="dialog"
            aria-modal="true"
            aria-labelledby="order-modal-title"
        >
            <div x-show="activeOrder !== null" x-transition class="w-full max-w-md overflow-hidden rounded-2xl border border-line bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-line px-5 py-4">
                    <div>
                        <h2 id="order-modal-title" class="font-mono text-lg font-extrabold text-ink" x-text="activeOrder?.code"></h2>
                        <span class="badge mt-1 px-2.5 py-0.5 text-xs" :class="activeOrder?.statusClass" x-text="activeOrder?.statusLabel"></span>
                    </div>
                    <button type="button" x-on:click="activeOrder = null" class="grid size-8 flex-none place-items-center rounded-lg text-lg text-subtle hover:bg-navy-50 hover:text-ink" aria-label="Tutup popup">×</button>
                </div>

                <dl class="divide-y divide-line px-5 text-sm">
                    <div class="flex justify-between gap-4 py-3">
                        <dt class="text-muted">Pembeli</dt>
                        <dd class="text-right">
                            <span class="block font-bold text-ink" x-text="activeOrder?.buyerName"></span>
                            <span class="text-muted" x-text="activeOrder?.buyerEmail"></span>
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4 py-3">
                        <dt class="text-muted">Paket</dt>
                        <dd class="text-right font-semibold text-ink" x-text="activeOrder?.plan"></dd>
                    </div>
                    <div class="flex justify-between gap-4 py-3">
                        <dt class="text-muted">Pembayaran</dt>
                        <dd class="text-right">
                            <span class="block font-semibold text-ink" x-text="activeOrder?.gateway"></span>
                            <span class="text-muted" x-text="activeOrder?.method"></span>
                        </dd>
                    </div>
                    <div class="space-y-1 py-3">
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted">Subtotal</dt>
                            <dd class="text-ink" x-text="activeOrder?.subtotal"></dd>
                        </div>
                        <div x-show="activeOrder?.discount" class="flex justify-between gap-4 text-brand-700">
                            <dt>Diskon</dt>
                            <dd x-text="activeOrder?.discount"></dd>
                        </div>
                        <div class="flex justify-between gap-4 pt-1 text-base font-extrabold text-ink">
                            <dt>Total</dt>
                            <dd x-text="activeOrder?.total"></dd>
                        </div>
                    </div>
                    <div class="flex justify-between gap-4 py-3">
                        <dt class="text-muted">Dibuat</dt>
                        <dd class="text-ink" x-text="activeOrder?.createdAt"></dd>
                    </div>
                    <div x-show="activeOrder?.paidAt" class="flex justify-between gap-4 py-3">
                        <dt class="text-muted">Dibayar</dt>
                        <dd class="text-ink" x-text="activeOrder?.paidAt"></dd>
                    </div>
                    <div x-show="activeOrder?.subscription" class="flex justify-between gap-4 py-3">
                        <dt class="text-muted">Langganan</dt>
                        <dd class="font-semibold text-brand-700" x-text="activeOrder?.subscription"></dd>
                    </div>
                </dl>

                <div class="flex items-center justify-end gap-2 border-t border-line bg-canvas/60 px-5 py-3.5">
                    <a :href="activeOrder?.showUrl" wire:navigate x-on:click="activeOrder = null" class="btn btn-outline btn-sm text-sm">Halaman detail</a>
                    <button type="button" x-on:click="activeOrder = null" class="btn btn-primary btn-sm text-sm">Tutup</button>
                </div>
            </div>
        </div>
    </template>
</div>
