@php use App\Support\Money; @endphp
<div>
    <x-page-header title="Pesanan" description="Verifikasi bukti transfer untuk mengaktifkan langganan." />

    <div class="mb-4 flex flex-wrap gap-3">
        <select wire:model.live="status" class="field w-auto" aria-label="Filter status pesanan">
            <option value="perlu-verifikasi">Perlu verifikasi (ada bukti)</option>
            <option value="">Semua status</option>
            @foreach ($statuses as $item)
                <option value="{{ $item->value }}">{{ $item->label() }}</option>
            @endforeach
        </select>
        <input type="search" wire:model.live.debounce.400ms="search" class="field max-w-xs" placeholder="Kode, nama, atau email" aria-label="Cari pesanan">
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead><tr><th>Kode</th><th>Pembeli</th><th>Paket</th><th>Total</th><th>Status</th><th>Dibuat</th><th></th></tr></thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr wire:key="order-{{ $order->id }}">
                            <td class="font-mono text-xs">{{ $order->code }}</td>
                            <td><span class="font-semibold">{{ $order->user->name }}</span><br><span class="text-xs text-subtle">{{ $order->user->email }}</span></td>
                            <td>{{ $order->plan->name }} <span class="text-xs text-subtle">· {{ $order->durationLabel() }}</span></td>
                            <td class="font-semibold">{{ Money::rupiah($order->total) }}</td>
                            <td>
                                <span class="badge {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
                                @if ($order->isPending() && $order->proof_path) <span class="badge bg-navy-600 text-white">Ada bukti</span> @endif
                            </td>
                            <td class="text-xs text-muted">{{ $order->created_at->translatedFormat('d M Y H:i') }}</td>
                            <td class="text-right"><a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="btn btn-ghost btn-sm">Periksa</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-muted">Tidak ada pesanan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
</div>
