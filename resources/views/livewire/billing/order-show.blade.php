@php use App\Support\Money; @endphp
<div class="mx-auto max-w-4xl">
    <a href="{{ route('orders.index') }}" wire:navigate class="text-sm font-semibold text-muted hover:text-ink">← Pesanan saya</a>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-extrabold tracking-tight">Pesanan {{ $order->code }}</h1>
        <span class="badge {{ $order->status->badgeClass() }} text-sm">{{ $order->status->label() }}</span>
    </div>

    <section class="card mt-6 p-5">
        <dl class="grid gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-subtle">Paket</dt><dd class="font-semibold">{{ $order->plan->name }} · {{ $order->durationLabel() }}</dd></div>
            <div><dt class="text-subtle">Metode</dt><dd class="font-semibold">{{ ($instructions['charge']['channel'] ?? null) ? $instructions['channels'][$instructions['charge']['channel']]['label'] : $methodLabel }}</dd></div>
            <div><dt class="text-subtle">Subtotal</dt><dd>{{ Money::rupiah($order->subtotal) }}</dd></div>
            @if ($order->discount > 0)
                <div><dt class="text-subtle">Diskon ({{ $order->voucher?->code }})</dt><dd class="text-brand-700">−{{ Money::rupiah($order->discount) }}</dd></div>
            @endif
            <div><dt class="text-subtle">Total bayar</dt><dd class="text-xl font-extrabold">{{ Money::rupiah($order->total) }}</dd></div>
            <div><dt class="text-subtle">Dibuat</dt><dd>{{ $order->created_at->translatedFormat('d F Y, H:i') }}</dd></div>
        </dl>
    </section>

    @if ($order->status === \App\Enums\OrderStatus::Paid)
        <section @class(['card mt-6 flex items-start gap-4 border-brand-100 bg-brand-50 p-5', 'paid-burst' => $justPaid])
                 role="status"
                 @if ($justPaid) x-data x-init="document.title = '✓ Lunas · ' + document.title.replace(/^✓ Lunas · /, '')" @endif>
            <span class="paid-check grid size-11 flex-none place-items-center rounded-full bg-brand-600 text-white shadow-lg shadow-brand-600/30">
                <x-icon name="check" class="size-5" />
            </span>
            <div>
                <p class="font-bold text-brand-700">{{ $justPaid ? 'Pembayaran baru saja diterima!' : 'Pembayaran diterima' }}</p>
                <p class="mt-1 text-sm text-muted">Langganan aktif sampai {{ $order->subscription?->ends_at->translatedFormat('d F Y') }}. Selamat belajar!</p>
                <a href="{{ route('dashboard') }}" wire:navigate class="btn btn-primary btn-sm mt-4">Mulai belajar</a>
            </div>
        </section>
    @elseif ($order->status === \App\Enums\OrderStatus::Rejected)
        <section class="card mt-6 border-coral-100 p-5">
            <p class="font-bold text-coral-600">Pembayaran ditolak</p>
            <p class="mt-1 text-sm text-muted">{{ $order->admin_note ?: 'Bukti pembayaran tidak dapat diverifikasi.' }} Hubungi {{ $instructions['contact'] ?? 'admin' }} jika ada pertanyaan.</p>
        </section>
    @elseif ($order->isPending())
        @if (($instructions['type'] ?? '') === 'midtrans')
            @php
                $channels = $instructions['channels'];
                $charge = $instructions['charge'];
                $groups = ['instant' => 'QRIS & e-wallet', 'va' => 'Transfer bank · Virtual Account'];
                if (($channels[$instructions['preferred'][0]]['group'] ?? 'instant') === 'va') {
                    $groups = array_reverse($groups, true);
                }
            @endphp

            @if ($charge === null)
                {{-- Step 1: choose a channel --}}
                <section class="card mt-6 p-5 sm:p-6">
                    <div class="flex flex-wrap items-end justify-between gap-2">
                        <div>
                            <h2 class="text-lg font-bold">Pilih cara bayar</h2>
                            <p class="mt-0.5 text-sm text-muted">Semua metode terkonfirmasi otomatis, langganan langsung aktif.</p>
                        </div>
                        <p class="text-xs text-subtle">Bayar sebelum {{ $order->expires_at->setTimezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB</p>
                    </div>

                    <div class="mt-5 space-y-5">
                        @foreach ($groups as $group => $groupLabel)
                            <fieldset>
                                <legend class="mb-2 text-[11px] font-bold tracking-[0.14em] text-subtle uppercase">{{ $groupLabel }}</legend>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    @foreach ($channels as $key => $item)
                                        @continue($item['group'] !== $group)
                                        <label wire:key="channel-{{ $key }}" class="pay-option">
                                            <input type="radio" wire:model="channel" value="{{ $key }}" class="sr-only">
                                            <span class="grid h-9 w-12 flex-none place-items-center rounded-lg text-[11px] font-extrabold tracking-tight {{ $item['tone'] }}">{{ $item['short'] }}</span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm font-bold text-ink">{{ $item['label'] }}</span>
                                                <span class="block text-xs text-subtle">
                                                    @switch($key)
                                                        @case('qris') Semua bank & e-wallet @break
                                                        @case('gopay') Bayar di aplikasi Gojek @break
                                                        @case('shopeepay') Buka dari HP @break
                                                        @default Transfer dari m-banking / ATM
                                                    @endswitch
                                                </span>
                                            </span>
                                            <span class="pay-option-check" aria-hidden="true"><x-icon name="check" class="size-3.5" /></span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                    </div>

                    @error('channel') <p class="field-error mt-4" role="alert">{{ $message }}</p> @enderror

                    <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-5">
                        <p class="text-sm text-muted">Total <span class="text-lg font-extrabold text-ink">{{ Money::rupiah($order->total) }}</span></p>
                        <button type="button" wire:click="pay" class="btn btn-primary px-6 py-3" wire:loading.attr="disabled" wire:target="pay">
                            <span wire:loading.remove wire:target="pay">Lanjutkan pembayaran</span>
                            <span wire:loading wire:target="pay">Menyiapkan pembayaran…</span>
                        </button>
                    </div>
                </section>
            @else
                {{-- Step 2: pay with the generated code --}}
                @php
                    $meta = $channels[$charge['channel']];
                    $expiresAt = isset($charge['expires_at'])
                        ? \Illuminate\Support\Carbon::parse($charge['expires_at'], 'Asia/Jakarta')
                        : $order->expires_at;
                @endphp
                <section class="card mt-6 overflow-hidden" wire:poll.4s.keep-alive="pollStatus"
                         x-data
                         x-on:visibilitychange.document="document.hidden || $wire.pollStatus()"
                         x-on:focus.window="$wire.pollStatus()">
                    <div class="flex flex-wrap items-center justify-between gap-4 bg-gradient-to-br from-navy-600 to-navy-700 p-5 text-white sm:p-6">
                        <div class="flex items-center gap-3">
                            <span class="grid h-10 w-14 place-items-center rounded-xl text-xs font-extrabold {{ $meta['tone'] }}">{{ $meta['short'] }}</span>
                            <div>
                                <p class="font-bold">{{ $meta['label'] }}</p>
                                <p class="flex items-center gap-2 text-xs text-white/70"><span class="pulse-dot size-2 rounded-full bg-sun-400"></span> Menunggu pembayaran · dicek otomatis</p>
                            </div>
                        </div>
                        <div class="text-right"
                             x-data="{
                                 end: {{ $expiresAt->getTimestampMs() }},
                                 left: '',
                                 timer: null,
                                 init() { this.tick(); this.timer = setInterval(() => this.tick(), 1000); },
                                 destroy() { clearInterval(this.timer); },
                                 tick() {
                                     const s = Math.max(0, Math.floor((this.end - Date.now()) / 1000));
                                     const pad = (n) => String(n).padStart(2, '0');
                                     this.left = pad(Math.floor(s / 3600)) + ':' + pad(Math.floor(s % 3600 / 60)) + ':' + pad(s % 60);
                                 },
                             }">
                            <p class="text-xs text-white/60">Bayar dalam</p>
                            <p class="font-mono text-xl font-bold tabular-nums text-sun-400" x-text="left">--:--:--</p>
                        </div>
                    </div>

                    <div class="grid gap-6 p-5 sm:p-6 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-bold tracking-[0.14em] text-subtle uppercase">Total tagihan</p>
                            <x-copy-value :value="(string) $order->total" :display="Money::rupiah($order->total)" class="text-2xl" />
                            <p class="mt-1 text-xs text-subtle">Bayar tepat sesuai nominal.</p>

                            <div class="mt-6">
                                @if (! empty($charge['va_number']))
                                    <p class="text-xs font-bold tracking-[0.14em] text-subtle uppercase">Nomor Virtual Account</p>
                                    <x-copy-value :value="$charge['va_number']" class="font-mono text-2xl tracking-wider" />
                                @elseif (! empty($charge['bill_key']))
                                    <p class="text-xs font-bold tracking-[0.14em] text-subtle uppercase">Kode perusahaan</p>
                                    <x-copy-value :value="$charge['biller_code']" class="font-mono text-xl" />
                                    <p class="mt-4 text-xs font-bold tracking-[0.14em] text-subtle uppercase">Kode bayar</p>
                                    <x-copy-value :value="$charge['bill_key']" class="font-mono text-2xl tracking-wider" />
                                @endif

                                @if (! empty($charge['qr_url']))
                                    <div class="w-fit rounded-2xl border border-line bg-white p-3">
                                        <img src="{{ $charge['qr_url'] }}" alt="Kode QR pembayaran {{ $meta['label'] }}" width="220" height="220" class="size-[220px]">
                                    </div>
                                    <p class="mt-2 text-xs text-subtle">Pindai dengan aplikasi pembayaranmu.</p>
                                @endif

                                @if (! empty($charge['deeplink']))
                                    <a href="{{ $charge['deeplink'] }}" class="btn btn-navy mt-4 w-full sm:w-auto" rel="noopener">
                                        Buka aplikasi {{ $meta['label'] }}
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="rounded-2xl bg-canvas p-5">
                            <h3 class="font-bold">Cara bayar</h3>
                            <ol class="mt-3 space-y-3 text-sm text-muted">
                                @foreach ($meta['steps'] as $step)
                                    <li class="flex gap-3">
                                        <span class="grid size-6 flex-none place-items-center rounded-full bg-white text-xs font-bold text-navy-600 shadow-sm">{{ $loop->iteration }}</span>
                                        <span>{{ $step }}</span>
                                    </li>
                                @endforeach
                                <li class="flex gap-3">
                                    <span class="grid size-6 flex-none place-items-center rounded-full bg-brand-600 text-white"><x-icon name="check" class="size-3.5" /></span>
                                    <span>Halaman ini otomatis berubah begitu pembayaran diterima.</span>
                                </li>
                            </ol>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 border-t border-line px-5 py-4 sm:px-6">
                        <button type="button" wire:click="checkPaymentStatus" class="btn btn-primary" wire:loading.attr="disabled" wire:target="checkPaymentStatus">
                            <span wire:loading.remove wire:target="checkPaymentStatus">Saya sudah bayar</span>
                            <span wire:loading wire:target="checkPaymentStatus">Memeriksa…</span>
                        </button>
                        <button type="button" wire:click="changeMethod" wire:confirm="Ganti metode pembayaran? Kode bayar yang sekarang akan dibatalkan." class="btn btn-outline">Ganti metode</button>
                        <p class="ml-auto flex items-center gap-2 text-xs text-subtle" aria-live="polite">
                            <span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-brand-500 opacity-60"></span><span class="relative inline-flex size-2 rounded-full bg-brand-500"></span></span>
                            <span>Live{{ $lastCheckedAt ? ' · dicek '.$lastCheckedAt.' WIB' : '' }}</span>
                            <span wire:loading wire:target="pollStatus" class="text-brand-700">memeriksa…</span>
                        </p>
                        @if ($notice)
                            <p class="w-full text-sm text-muted" role="status">{{ $notice }}</p>
                        @endif
                    </div>

                    @if ($instructions['simulator_url'])
                        <p class="border-t border-dashed border-sun-400/60 bg-sun-100/60 px-5 py-3 text-xs text-sun-600 sm:px-6">
                            Mode sandbox: uang sungguhan tidak ditarik. Selesaikan pembayaran lewat
                            <a href="{{ $instructions['simulator_url'] }}" target="_blank" rel="noopener noreferrer" class="font-bold underline">simulator Midtrans</a>
                            dengan nomor VA atau kode QR di atas.
                        </p>
                    @endif
                </section>
            @endif
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
