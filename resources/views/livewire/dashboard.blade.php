<div class="space-y-8">
    <x-page-header :title="'Halo, '.$user->publicName()"
                   :description="$user->targetCategory ? 'Kamu sedang mempersiapkan '.$user->targetCategory->name.'. Lanjutkan dari bagian terakhir.' : 'Pilih materi atau try out untuk mulai belajar.'" />

    @if ($runningAttempt)
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-sun-400/50 bg-sun-100 px-5 py-4">
            <div>
                <p class="text-sm font-bold text-sun-600">Ujian sedang berjalan</p>
                <p class="mt-0.5 font-semibold">{{ $runningAttempt->package->title }} · sisa {{ ceil($runningAttempt->remainingSeconds() / 60) }} menit</p>
            </div>
            <a href="{{ route('exam.show', $runningAttempt) }}" class="btn btn-navy">Lanjutkan ujian</a>
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        <section class="card p-5">
            <h2 class="text-sm font-bold text-subtle">Langganan</h2>
            @forelse ($subscriptions->take(2) as $subscription)
                <p class="mt-2 font-bold">{{ $subscription->plan->name }}</p>
                <p class="text-sm text-muted">Aktif sampai {{ $subscription->ends_at->translatedFormat('d F Y') }} · {{ $subscription->daysLeft() }} hari lagi</p>
            @empty
                <p class="mt-2 font-bold">Akun gratis</p>
                <p class="text-sm text-muted">Kamu bisa mencoba demo try out dan materi bab pertama.</p>
                <a href="{{ route('plans.index') }}" wire:navigate class="btn btn-primary btn-sm mt-4">Lihat paket langganan</a>
            @endforelse
        </section>

        <section class="card p-5">
            <h2 class="text-sm font-bold text-subtle">Progres materi</h2>
            <p class="mt-2 text-3xl font-extrabold">{{ $completedMaterials }}<span class="text-base font-semibold text-subtle"> / {{ $totalMaterials }}</span></p>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-navy-50">
                <div class="h-full rounded-full bg-brand-500" style="width: {{ $totalMaterials ? min(100, round($completedMaterials / $totalMaterials * 100)) : 0 }}%"></div>
            </div>
            <a href="{{ route('learn.index') }}" wire:navigate class="mt-4 inline-block text-sm font-semibold text-brand-700 hover:underline">Lanjut belajar →</a>
        </section>

        <section class="card p-5">
            <h2 class="text-sm font-bold text-subtle">Try out selesai</h2>
            <p class="mt-2 text-3xl font-extrabold">{{ $recentAttempts->count() }}</p>
            @if ($recentAttempts->isNotEmpty())
                <p class="text-sm text-muted">Nilai terakhir {{ $recentAttempts->first()->percentage() }}% di {{ $recentAttempts->first()->package->title }}</p>
            @else
                <p class="text-sm text-muted">Belum ada. Coba demo try out gratis di bawah.</p>
            @endif
        </section>
    </div>

    <div class="grid gap-6 lg:grid-cols-5">
        <section class="card p-6 lg:col-span-3">
            <div class="flex items-center justify-between">
                <h2 class="font-bold">Perkembangan nilai</h2>
                <span class="text-xs text-subtle">% benar per try out</span>
            </div>
            @if ($chart->count() >= 2)
                <div class="mt-6 flex h-44 items-end gap-3" role="img" aria-label="Grafik perkembangan nilai try out">
                    @foreach ($chart as $point)
                        <div class="flex flex-1 flex-col items-center gap-2" title="{{ $point['title'] }}: {{ $point['value'] }}%">
                            <span class="text-xs font-bold text-ink">{{ round($point['value']) }}</span>
                            <div class="w-full max-w-12 rounded-t-lg bg-gradient-to-t from-brand-600 to-brand-500" style="height: {{ max(4, $point['value']) }}%"></div>
                            <span class="text-[11px] text-subtle">{{ $point['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-6 rounded-xl bg-canvas px-4 py-8 text-center text-sm text-muted">Grafik muncul setelah kamu menyelesaikan minimal dua try out.</p>
            @endif
        </section>

        <section class="card p-6 lg:col-span-2">
            <h2 class="font-bold">Rekomendasi terakhir</h2>
            @forelse ($latestRecommendations as $recommendation)
                <div class="mt-4 rounded-xl bg-brand-50 px-4 py-3">
                    <p class="text-sm font-bold text-brand-700">{{ $recommendation['topic'] }}</p>
                    <p class="mt-1 text-sm text-muted">{{ $recommendation['action'] }}</p>
                </div>
            @empty
                <p class="mt-4 text-sm text-muted">Rekomendasi belajar muncul setelah kamu mengerjakan try out.</p>
            @endforelse
        </section>
    </div>

    <section>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-bold">Try out untukmu</h2>
            <a href="{{ route('tryout.index') }}" wire:navigate class="text-sm font-semibold text-brand-700 hover:underline">Lihat semua</a>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($suggestedPackages as $package)
                <a wire:key="suggested-{{ $package->id }}" href="{{ route('tryout.show', $package) }}" wire:navigate class="card group p-5 transition hover:border-brand-500">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold text-subtle">{{ $package->category->name }}</span>
                        @if ($package->is_free)
                            <span class="badge bg-sun-100 text-sun-600">Gratis</span>
                        @endif
                    </div>
                    <p class="mt-2 font-bold group-hover:text-brand-700">{{ $package->title }}</p>
                    <p class="mt-1 text-sm text-muted">{{ $package->duration_minutes }} menit</p>
                </a>
            @endforeach
        </div>
    </section>

    @if ($recentAttempts->isNotEmpty())
        <section class="card overflow-hidden">
            <h2 class="border-b border-line px-5 py-4 font-bold">Riwayat try out</h2>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead><tr><th>Paket</th><th>Tanggal</th><th>Nilai</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($recentAttempts as $attempt)
                            <tr wire:key="attempt-{{ $attempt->id }}">
                                <td class="font-semibold">{{ $attempt->package->title }}</td>
                                <td class="text-muted">{{ $attempt->submitted_at->translatedFormat('d M Y, H:i') }}</td>
                                <td class="font-bold">{{ rtrim(rtrim(number_format($attempt->score, 2, ',', '.'), '0'), ',') }} <span class="font-normal text-subtle">({{ $attempt->percentage() }}%)</span></td>
                                <td>
                                    @if ($attempt->passed === true)
                                        <span class="badge bg-brand-50 text-brand-700">Lolos</span>
                                    @elseif ($attempt->passed === false)
                                        <span class="badge bg-coral-100 text-coral-600">Belum lolos</span>
                                    @else
                                        <span class="badge bg-navy-50 text-muted">Selesai</span>
                                    @endif
                                </td>
                                <td class="text-right"><a href="{{ route('results.show', $attempt) }}" wire:navigate class="text-sm font-semibold text-brand-700 hover:underline">Lihat hasil</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
