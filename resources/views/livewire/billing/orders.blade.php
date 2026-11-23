@php use App\Support\Money; @endphp
<div>
    <x-page-header title="Pesanan saya" description="Riwayat pembelian paket langganan." />

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead><tr><th>Kode</th><th>Paket</th><th>Total</th><th>Status</th><th>Tanggal</th><th></th></tr></thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr wire:key="order-{{ $order->id }}">
                            <td class="font-mono text-xs">{{ $order->code }}</td>
                            <td class="font-semibold">{{ $order->plan->name }} <span class="font-normal text-subtle">· {{ $order->durationLabel() }}</span></td>
                            <td>{{ Money::rupiah($order->total) }}</td>
                            <td><span class="badge {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span></td>
                            <td class="text-muted">{{ $order->created_at->translatedFormat('d M Y') }}</td>
                            <td class="text-right"><a href="{{ route('orders.show', $order) }}" wire:navigate class="text-sm font-semibold text-brand-700 hover:underline">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-muted">Belum ada pesanan. <a href="{{ route('plans.index') }}" wire:navigate class="font-semibold text-brand-700">Lihat paket</a></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
</div>
