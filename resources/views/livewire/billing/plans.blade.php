@php use App\Support\Money; @endphp
<div>
    <x-page-header title="Paket langganan" description="Pilih satu kategori ujian, atau Semua Akses untuk berlatih semua ujian dengan satu langganan. Harga per bulan makin hemat untuk durasi panjang." />

    @if ($subscriptions->isNotEmpty())
        <div class="mb-8 rounded-2xl border border-brand-100 bg-brand-50 px-5 py-4 text-sm">
            <p class="font-bold text-brand-700">Langganan aktif</p>
            <ul class="mt-1 text-muted">
                @foreach ($subscriptions as $subscription)
                    <li>{{ $subscription->plan->name }} · sampai {{ $subscription->ends_at->translatedFormat('d F Y') }}</li>
                @endforeach
            </ul>
            <p class="mt-2 text-muted">Membeli paket yang sama akan memperpanjang masa aktifnya.</p>
        </div>
    @endif

    @if ($allAccess)
        <section class="relative overflow-hidden rounded-3xl bg-navy-600 p-6 text-white sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-6">
                <div class="max-w-lg">
                    <span class="badge bg-sun-400 text-navy-700">Paling hemat</span>
                    <h2 class="mt-3 text-2xl font-extrabold">{{ $allAccess->name }}</h2>
                    <p class="mt-2 text-white/75">{{ $allAccess->description }} Termasuk TKA SD/SMP/SMA, UTBK-SNBT, CPNS, BUMN, dan Kedinasan.</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-white/70">mulai</p>
                    <p class="text-3xl font-extrabold">{{ Money::rupiahShort($allAccess->lowestMonthlyPrice()) }}<span class="text-base font-semibold text-white/70">/bulan</span></p>
                    <a href="{{ route('plans.checkout', $allAccess) }}" wire:navigate class="btn mt-4 bg-sun-400 text-navy-700 hover:bg-sun-400/90">Pilih Semua Akses</a>
                </div>
            </div>
        </section>
    @endif

    <h2 class="mt-10 mb-4 text-lg font-bold">Per kategori</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($categoryPlans as $plan)
            <div wire:key="plan-{{ $plan->id }}" @class(['card flex flex-col p-5', 'ring-2 ring-brand-500' => $plan->categories->contains('id', $targetCategoryId)])>
                <div class="flex items-center justify-between gap-2">
                    <h3 class="font-bold">{{ $plan->name }}</h3>
                    @if ($plan->categories->contains('id', $targetCategoryId))
                        <span class="badge bg-brand-50 text-brand-700">Sesuai tujuanmu</span>
                    @endif
                </div>
                <p class="mt-1 text-sm text-muted">{{ $plan->description }}</p>
                <p class="mt-4 text-2xl font-extrabold">{{ Money::rupiahShort($plan->lowestMonthlyPrice()) }}<span class="text-sm font-semibold text-subtle">/bulan</span></p>
                <p class="text-xs text-subtle">dengan durasi {{ $plan->prices->last()?->durationLabel() }}</p>
                <a href="{{ route('plans.checkout', $plan) }}" wire:navigate class="btn btn-outline mt-5">Pilih paket</a>
            </div>
        @endforeach
    </div>
</div>
