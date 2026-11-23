@php use App\Support\Money; @endphp
<div class="mx-auto max-w-3xl">
    <a href="{{ route('orders.index') }}" wire:navigate class="text-sm font-semibold text-muted hover:text-ink">← Pesanan saya</a>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-extrabold tracking-tight">Pesanan {{ $order->code }}</h1>
        <span class="badge {{ $order->status->badgeClass() }} text-sm">{{ $order->status->label() }}</span>
    </div>

    <section class="card mt-6 p-5">
        <dl class="grid gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-subtle">Paket</dt><dd class="font-semibold">{{ $order->plan->name }} · {{ $order->durationLabel() }}</dd></div>
            <div><dt class="text-subtle">Metode</dt><dd class="font-semibold">{{ $methodLabel }}</dd></div>
            <div><dt class="text-subtle">Subtotal</dt><dd>{{ Money::rupiah($order->subtotal) }}</dd></div>
            @if ($order->discount > 0)
                <div><dt class="text-subtle">Diskon ({{ $order->voucher?->code }})</dt><dd class="text-brand-700">−{{ Money::rupiah($order->discount) }}</dd></div>
            @endif
            <div><dt class="text-subtle">Total bayar</dt><dd class="text-xl font-extrabold">{{ Money::rupiah($order->total) }}</dd></div>
            <div><dt class="text-subtle">Dibuat</dt><dd>{{ $order->created_at->translatedFormat('d F Y, H:i') }}</dd></div>
        </dl>
    </section>

    @if ($order->status === \App\Enums\OrderStatus::Paid)
        <section class="card mt-6 border-brand-100 bg-brand-50 p-5">
            <p class="font-bold text-brand-700">Pembayaran diterima</p>
            <p class="mt-1 text-sm text-muted">Langganan aktif sampai {{ $order->subscription?->ends_at->translatedFormat('d F Y') }}. Selamat belajar!</p>
            <a href="{{ route('dashboard') }}" wire:navigate class="btn btn-primary btn-sm mt-4">Mulai belajar</a>
        </section>
    @elseif ($order->status === \App\Enums\OrderStatus::Rejected)
        <section class="card mt-6 border-coral-100 p-5">
            <p class="font-bold text-coral-600">Pembayaran ditolak</p>
            <p class="mt-1 text-sm text-muted">{{ $order->admin_note ?: 'Bukti pembayaran tidak dapat diverifikasi.' }} Hubungi {{ $instructions['contact'] ?? 'admin' }} jika ada pertanyaan.</p>
        </section>
    @elseif ($order->isPending())
        @if (($instructions['type'] ?? '') === 'midtrans')
            <section class="card mt-6 p-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-50 text-brand-700 font-bold text-sm">✓</span>
                    <div>
                        <h2 class="font-bold text-lg">Selesaikan Pembayaran</h2>
                        <p class="text-xs text-muted">Metode: <strong class="text-ink">{{ $methodLabel }}</strong> · Pembayaran Otomatis</p>
                    </div>
                </div>

                <p class="mt-3 text-sm text-muted">
                    Total tagihan kamu sebesar <strong class="text-ink">{{ Money::rupiah($order->total) }}</strong>. Silakan lanjutkan ke portal pembayaran resmi Midtrans (mendukung QRIS, GoPay, ShopeePay, dan Virtual Account semua bank).
                </p>

                <div class="mt-5 flex flex-wrap items-center gap-3" x-data="{
                    pay() {
                        if (typeof window.snap !== 'undefined' && '{{ $instructions['snap_token'] ?? '' }}') {
                            window.snap.pay('{{ $instructions['snap_token'] ?? '' }}', {
                                onSuccess: function() { window.location.reload(); },
                                onPending: function() { window.location.reload(); },
                                onError: function() { alert('Pembayaran gagal atau dibatalkan.'); },
                                onClose: function() {}
                            });
                        } else if ('{{ $instructions['redirect_url'] ?? '' }}') {
                            window.open('{{ $instructions['redirect_url'] ?? '' }}', '_blank');
                        }
                    }
                }">
                    @if (!empty($instructions['client_key']))
                        <button type="button" @click="pay()" class="btn btn-primary">
                            Bayar Sekarang (Midtrans Snap)
                        </button>
                    @endif
                    @if (!empty($instructions['redirect_url']))
                        <a href="{{ $instructions['redirect_url'] }}" target="_blank" rel="noopener noreferrer" class="btn {{ empty($instructions['client_key']) ? 'btn-primary' : 'btn-secondary' }}">
                            Buka Halaman Pembayaran Midtrans ↗
                        </a>
                    @endif
                    <button type="button" wire:click="checkPaymentStatus" class="btn btn-outline" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="checkPaymentStatus">🔄 Cek Status Pembayaran</span>
                        <span wire:loading wire:target="checkPaymentStatus">Memeriksa…</span>
                    </button>
                </div>

                <p class="mt-4 text-xs text-subtle">
                    Batas waktu pembayaran {{ $order->expires_at->translatedFormat('d F Y, H:i') }}. Setelah Anda menyelesaikan pembayaran, status langganan akan otomatis aktif.
                </p>
            </section>
        @else
            <section class="card mt-6 p-5">
                <h2 class="font-bold">Cara membayar</h2>
                <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-muted">
                    <li>Transfer tepat <strong class="text-ink">{{ Money::rupiah($order->total) }}</strong> ke salah satu rekening di bawah.</li>
                    <li>Tulis kode <strong class="font-mono text-ink">{{ $order->code }}</strong> di berita transfer.</li>
                    <li>Unggah foto atau tangkapan layar bukti transfer di bawah ini.</li>
                </ol>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($instructions['accounts'] ?? [] as $account)
                        <div class="rounded-xl border border-line p-4">
                            <p class="text-xs font-bold text-subtle">{{ $account['bank'] }}</p>
                            <p class="mt-1 font-mono text-lg font-bold" x-data>{{ $account['number'] }}
                                <button type="button" class="ml-1 align-middle text-xs font-semibold text-brand-700" @click="navigator.clipboard.writeText('{{ $account['number'] }}')">Salin</button>
                            </p>
                            <p class="text-sm text-muted">a.n. {{ $account['holder'] }}</p>
                        </div>
                    @endforeach
                </div>
                <p class="mt-4 text-xs text-subtle">Batas pembayaran {{ $order->expires_at->translatedFormat('d F Y, H:i') }}. Pesanan tanpa bukti bayar akan kedaluwarsa otomatis.</p>
            </section>
        @endif

        @if ($requiresProof)
            <section class="card mt-6 p-5">
                <h2 class="font-bold">Bukti pembayaran</h2>
                @if ($order->proof_path)
                    <p class="mt-2 text-sm text-muted">Bukti dikirim {{ $order->proof_uploaded_at->translatedFormat('d F Y, H:i') }}. Admin sedang memverifikasi. <a href="{{ route('orders.proof', $order) }}" target="_blank" class="font-semibold text-brand-700 hover:underline">Lihat bukti</a></p>
                    <p class="mt-1 text-xs text-subtle">Salah unggah? Kamu bisa mengganti bukti di bawah.</p>
                @endif
                <form wire:submit="uploadProof" class="mt-4 flex flex-wrap items-center gap-3">
                    <input type="file" wire:model="proof" accept="image/*,application/pdf" class="text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-navy-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-navy-600">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="proof,uploadProof">Kirim bukti</button>
                    <span wire:loading wire:target="proof" class="text-xs text-subtle">Mengunggah…</span>
                </form>
                @error('proof') <p class="field-error">{{ $message }}</p> @enderror
            </section>
        @endif

        <button type="button" wire:click="cancel" wire:confirm="Batalkan pesanan ini?" class="btn btn-ghost mt-4 text-coral-600">Batalkan pesanan</button>
    @endif
</div>

@if (!empty($instructions['client_key']) && !empty($instructions['snap_token']))
    @push('scripts')
        <script src="{{ ($instructions['is_production'] ?? false) ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ $instructions['client_key'] }}"></script>
    @endpush
@endif
