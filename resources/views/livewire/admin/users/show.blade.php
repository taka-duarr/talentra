@php use App\Support\Money; @endphp
<div>
    <a href="{{ route('admin.users.index') }}" wire:navigate class="text-sm font-semibold text-muted hover:text-ink">← Pengguna</a>
    <div class="mt-3 mb-6 flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-extrabold tracking-tight">{{ $user->name }}</h1>
        <span class="text-sm text-muted">{{ $user->email }}</span>
        @if ($user->isBlocked()) <span class="badge bg-coral-100 text-coral-600">Diblokir</span> @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="card p-5">
            <h2 class="font-bold">Langganan</h2>
            <ul class="mt-3 divide-y divide-line text-sm">
                @forelse ($subscriptions as $subscription)
                    <li wire:key="sub-{{ $subscription->id }}" class="flex items-center justify-between gap-3 py-2.5">
                        <span><strong>{{ $subscription->plan->name }}</strong><br><span class="text-xs text-subtle">{{ $subscription->starts_at->translatedFormat('d M Y') }} – {{ $subscription->ends_at->translatedFormat('d M Y') }} {{ $subscription->order_id ? '' : '· manual' }}</span></span>
                        @if ($subscription->ends_at->isFuture())
                            <button type="button" wire:click="endSubscription({{ $subscription->id }})" wire:confirm="Akhiri langganan ini sekarang?" class="text-xs font-semibold text-coral-600">Akhiri</button>
                        @else
                            <span class="text-xs text-subtle">Berakhir</span>
                        @endif
                    </li>
                @empty
                    <li class="py-3 text-muted">Belum pernah berlangganan.</li>
                @endforelse
            </ul>
            <form wire:submit="grantSubscription" class="mt-4 grid grid-cols-[1fr_90px_auto] gap-2 border-t border-line pt-4">
                <select wire:model="grantPlanId" class="field" aria-label="Paket">
                    <option value="">Pilih paket</option>
                    @foreach ($plans as $plan) <option value="{{ $plan->id }}">{{ $plan->name }}</option> @endforeach
                </select>
                <input type="number" min="1" wire:model="grantMonths" class="field" aria-label="Jumlah bulan">
                <button type="submit" class="btn btn-navy">Berikan</button>
            </form>
            @error('grantPlanId') <p class="field-error">{{ $message }}</p> @enderror
        </section>

        <section class="card space-y-4 p-5">
            <h2 class="font-bold">Akun</h2>
            @if ($user->is(auth()->user()))
                <p class="text-sm text-muted">Ini akunmu sendiri. Peran dan status blokir tidak bisa diubah dari sini.</p>
            @else
                <form wire:submit="updateRole" class="flex gap-2">
                    <select wire:model="role" class="field" aria-label="Peran">
                        @foreach ($roles as $item) <option value="{{ $item->value }}">{{ $item->label() }}</option> @endforeach
                    </select>
                    <button type="submit" class="btn btn-outline">Ubah peran</button>
                </form>
                <button type="button" wire:click="toggleBlock" wire:confirm="{{ $user->isBlocked() ? 'Aktifkan kembali akun ini?' : 'Blokir akun ini? Pengguna akan langsung keluar.' }}" class="btn {{ $user->isBlocked() ? 'btn-outline' : 'btn-danger' }}">
                    {{ $user->isBlocked() ? 'Aktifkan akun' : 'Blokir akun' }}
                </button>
            @endif
        </section>

        <section class="card overflow-hidden">
            <h2 class="border-b border-line px-5 py-4 font-bold">Try out terakhir</h2>
            <ul class="divide-y divide-line text-sm">
                @forelse ($attempts as $attempt)
                    <li class="flex justify-between px-5 py-2.5">
                        <a href="{{ route('admin.attempts.show', $attempt) }}" wire:navigate class="hover:text-brand-700">{{ $attempt->package->title }} <span class="text-xs text-subtle">{{ $attempt->started_at->translatedFormat('d M Y') }}</span></a>
                        <span>{{ $attempt->isInProgress() ? 'Berjalan' : $attempt->percentage().'%' }} @if ($attempt->tab_switches) <span class="text-xs text-sun-600">· pindah tab {{ $attempt->tab_switches }}×</span> @endif</span>
                    </li>
                @empty
                    <li class="px-5 py-4 text-muted">Belum ada.</li>
                @endforelse
            </ul>
        </section>

        <section class="card overflow-hidden">
            <h2 class="border-b border-line px-5 py-4 font-bold">Pesanan</h2>
            <ul class="divide-y divide-line text-sm">
                @forelse ($orders as $order)
                    <li class="flex justify-between px-5 py-2.5">
                        <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="font-mono text-xs text-brand-700">{{ $order->code }}</a>
                        <span>{{ Money::rupiah($order->total) }} · <span class="badge {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span></span>
                    </li>
                @empty
                    <li class="px-5 py-4 text-muted">Belum ada.</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
