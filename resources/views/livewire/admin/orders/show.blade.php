@php use App\Support\Money; @endphp
<div>
    <a href="{{ route('admin.orders.index') }}" wire:navigate class="text-sm font-semibold text-muted hover:text-ink">← Pesanan</a>
    <div class="mt-3 mb-6 flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-extrabold tracking-tight">Pesanan {{ $order->code }}</h1>
        <span class="badge {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="card p-5">
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-subtle">Pembeli</dt><dd class="font-semibold">{{ $order->user->name }}</dd><dd class="text-xs text-subtle">{{ $order->user->email }}</dd></div>
                <div><dt class="text-subtle">Paket</dt><dd class="font-semibold">{{ $order->plan->name }} · {{ $order->durationLabel() }}</dd></div>
                <div><dt class="text-subtle">Subtotal</dt><dd>{{ Money::rupiah($order->subtotal) }}</dd></div>
                <div><dt class="text-subtle">Diskon</dt><dd>{{ $order->discount ? '−'.Money::rupiah($order->discount).' ('.$order->voucher?->code.')' : '—' }}</dd></div>
                <div><dt class="text-subtle">Total yang harus ditransfer</dt><dd class="text-xl font-extrabold">{{ Money::rupiah($order->total) }}</dd></div>
                <div><dt class="text-subtle">Metode dipilih</dt><dd>{{ config('talentra.payments.methods')[$order->payment_method] ?? '—' }}</dd></div>
                <div><dt class="text-subtle">Dibuat</dt><dd>{{ $order->created_at->translatedFormat('d M Y H:i') }}</dd></div>
                <div><dt class="text-subtle">Batas bayar</dt><dd>{{ $order->expires_at->translatedFormat('d M Y H:i') }}</dd></div>
                @if ($order->verifier)
                    <div class="col-span-2"><dt class="text-subtle">Diverifikasi oleh</dt><dd>{{ $order->verifier->name }} @if ($order->paid_at) · {{ $order->paid_at->translatedFormat('d M Y H:i') }} @endif</dd></div>
                @endif
                @if ($order->subscription)
                    <div class="col-span-2"><dt class="text-subtle">Langganan</dt><dd class="font-semibold text-brand-700">Aktif {{ $order->subscription->starts_at->translatedFormat('d M Y') }} – {{ $order->subscription->ends_at->translatedFormat('d M Y') }}</dd></div>
                @endif
            </dl>

            @if ($order->isPending() || $order->status === \App\Enums\OrderStatus::Expired)
                <div class="mt-6 space-y-3 border-t border-line pt-5">
                    <button type="button" wire:click="approve" wire:confirm="Setujui pembayaran {{ Money::rupiah($order->total) }} dan aktifkan langganan?" class="btn btn-primary w-full">Setujui & aktifkan langganan</button>
                    @if ($order->isPending())
                        <div>
                            <label for="note" class="field-label">Alasan penolakan</label>
                            <textarea id="note" wire:model="note" rows="2" class="field" placeholder="Contoh: nominal transfer tidak sesuai"></textarea>
                            @error('note') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <button type="button" wire:click="reject" wire:confirm="Tolak pesanan ini?" class="btn btn-outline w-full text-coral-600">Tolak pesanan</button>
                    @endif
                </div>
            @elseif ($order->admin_note)
                <p class="mt-5 rounded-xl bg-canvas p-3 text-sm text-muted">Catatan: {{ $order->admin_note }}</p>
            @endif
        </section>

        <section class="card p-5">
            <h2 class="font-bold">Bukti pembayaran</h2>
            @if ($order->proof_path)
                <p class="mt-1 text-xs text-subtle">Diunggah {{ $order->proof_uploaded_at?->translatedFormat('d M Y H:i') }}</p>
                @if ($isImage)
                    <img src="{{ route('orders.proof', $order) }}" alt="Bukti pembayaran {{ $order->code }}" class="mt-4 max-h-[520px] w-full rounded-xl border border-line object-contain">
                @endif
                <a href="{{ route('orders.proof', $order) }}" target="_blank" class="btn btn-outline btn-sm mt-3">Buka file</a>
            @else
                <p class="mt-3 text-sm text-muted">Pembeli belum mengunggah bukti pembayaran.</p>
            @endif
        </section>
    </div>
</div>
