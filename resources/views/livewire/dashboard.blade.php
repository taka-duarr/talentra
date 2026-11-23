@php
    $ringTotal = 2 * M_PI * 26;
    $weekdays = ['Sn', 'Sl', 'Rb', 'Km', 'Jm', 'Sb', 'Mg'];
    $materialPercent = $stats['totalMaterials'] ? min(100, round($stats['completedMaterials'] / $stats['totalMaterials'] * 100)) : 0;
@endphp
<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_310px]">
    {{-- ================= Main column ================= --}}
    <div class="min-w-0 space-y-6">
        @if ($runningAttempt)
            <a href="{{ route('exam.show', $runningAttempt) }}" class="rise shine flex flex-wrap items-center gap-4 rounded-2xl bg-gradient-to-r from-sun-400 to-[#ffd877] px-5 py-4 text-ink shadow-lg shadow-sun-400/30" style="--i:0">
                <span class="pulse-dot grid size-10 place-items-center rounded-xl bg-white/70"><x-icon name="clock" /></span>
                <span class="min-w-0 flex-1">
                    <span class="block text-xs font-bold tracking-wide uppercase">Ujian sedang berjalan</span>
                    <span class="block truncate font-bold">{{ $runningAttempt->package->title }} · sisa {{ ceil($runningAttempt->remainingSeconds() / 60) }} menit</span>
                </span>
                <span class="btn btn-navy btn-sm">Lanjutkan <x-icon name="arrow-right" class="size-4" /></span>
            </a>
        @endif

        {{-- Stat cards --}}
        <section class="grid grid-cols-2 gap-4 lg:grid-cols-4" aria-label="Ringkasan belajar">
            <article class="stat-card rise" style="--i:1">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-bold text-subtle">Nilai terakhir</h2>
                    <span class="grid size-7 place-items-center rounded-lg bg-coral-100 text-coral-600"><x-icon name="chart" class="size-4" /></span>
                </div>
                <div class="mt-3 flex items-end justify-between gap-3">
                    <p class="text-2xl font-extrabold tracking-tight"><span x-data="countUp({{ round($stats['lastScore']) }})" x-text="display">{{ round($stats['lastScore']) }}</span><span class="text-sm font-semibold text-subtle">%</span></p>
                    <div class="flex h-10 items-end gap-1" aria-hidden="true">
                        @if ($stats['scoreBars']->count() >= 2)
                            @foreach ($stats['scoreBars'] as $index => $bar)
                                <span class="grow-bar w-1.5 rounded-full {{ $loop->last ? 'bg-coral-500' : 'bg-coral-500/35' }}" style="height: {{ max(8, $bar) }}%; --i: {{ $index }}"></span>
                            @endforeach
                        @else
                            @foreach ([30, 55, 40, 70, 50, 62] as $index => $bar)
                                <span class="grow-bar w-1.5 rounded-full {{ $loop->last ? 'bg-coral-500/60' : 'bg-navy-100' }}" style="height: {{ $bar }}%; --i: {{ $index }}"></span>
                            @endforeach
                        @endif
                    </div>
                </div>
                <p class="mt-1 text-[11px] text-subtle">{{ $stats['attempts'] }} try out selesai</p>
            </article>

            <article class="stat-card rise" style="--i:2">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-bold text-subtle">Akurasi jawaban</h2>
                    <span class="grid size-7 place-items-center rounded-lg bg-brand-50 text-brand-700"><x-icon name="target" class="size-4" /></span>
                </div>
                <div class="mt-2 flex items-center justify-between gap-3">
                    <p class="text-2xl font-extrabold tracking-tight"><span x-data="countUp({{ $stats['accuracy'] }})" x-text="display">{{ $stats['accuracy'] }}</span><span class="text-sm font-semibold text-subtle">%</span></p>
                    <svg class="size-14 -rotate-90" viewBox="0 0 60 60" aria-hidden="true">
                        <circle cx="30" cy="30" r="26" fill="none" stroke="currentColor" stroke-width="6" class="text-brand-50" />
                        <circle cx="30" cy="30" r="26" fill="none" stroke="url(#ringGradient)" stroke-width="6" stroke-linecap="round"
                                class="ring-fill" stroke-dasharray="{{ $ringTotal }}"
                                style="--ring-total: {{ $ringTotal }}; --ring-offset: {{ $ringTotal * (1 - $stats['accuracy'] / 100) }}" />
                        <defs><linearGradient id="ringGradient" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#2dd4bf" /><stop offset="1" stop-color="#0d9488" /></linearGradient></defs>
                    </svg>
                </div>
                <p class="mt-1 text-[11px] text-subtle">dari semua soal yang dikerjakan</p>
            </article>

            <article class="stat-card rise" style="--i:3">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-bold text-subtle">Materi selesai</h2>
                    <span class="grid size-7 place-items-center rounded-lg bg-sky-100 text-sky-600"><x-icon name="book" class="size-4" /></span>
                </div>
                <p class="mt-3 text-2xl font-extrabold tracking-tight"><span x-data="countUp({{ $stats['completedMaterials'] }})" x-text="display">{{ $stats['completedMaterials'] }}</span><span class="text-sm font-semibold text-subtle"> / {{ $stats['totalMaterials'] }}</span></p>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-sky-100">
                    <div class="fill-bar h-full rounded-full bg-gradient-to-r from-sky-400 to-sky-600" style="width: {{ $materialPercent }}%"></div>
                </div>
                <p class="mt-1.5 text-[11px] text-subtle">{{ $materialPercent }}% materi tujuanmu</p>
            </article>

            <article class="stat-card rise" style="--i:4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-bold text-subtle">Poin</h2>
                    <span class="grid size-7 place-items-center rounded-lg bg-sun-100 text-sun-600"><x-icon name="flame" class="size-4" /></span>
                </div>
                <p class="mt-3 text-2xl font-extrabold tracking-tight"><span x-data="countUp({{ $stats['points'] }})" x-text="display">{{ $stats['points'] }}</span></p>
                <svg class="mt-1 h-7 w-full" viewBox="0 0 120 28" preserveAspectRatio="none" aria-hidden="true">
                    <path class="draw-line" pathLength="1" d="M0,20 C12,6 22,6 32,15 S52,26 62,14 S84,2 94,10 S112,22 120,8" fill="none" stroke="#e0a200" stroke-width="2.5" stroke-linecap="round" />
                </svg>
                <p class="text-[11px] text-subtle">+{{ $stats['weeklyPoints'] }} minggu ini</p>
            </article>
        </section>

        {{-- Chart + featured --}}
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1.55fr)_minmax(0,1fr)]">
            <section class="stat-card rise p-5" style="--i:5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="font-bold">Perkembangan nilai</h2>
                    <span class="rounded-full bg-canvas px-3 py-1 text-xs font-semibold text-muted">{{ $chart ? count($chart['points']).' try out terakhir' : 'Belum ada data' }}</span>
                </div>

                @if ($chart)
                    @php
                        $last = end($chart['points']);
                        $width = \App\Livewire\Dashboard::CHART_WIDTH;
                        $height = \App\Livewire\Dashboard::CHART_HEIGHT;
                    @endphp
                    <div class="relative mt-4 h-48">
                    <svg class="absolute inset-0 h-full w-full overflow-visible" viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none" role="img" aria-label="Grafik nilai: {{ collect($chart['points'])->map(fn ($p) => $p['label'].' '.$p['value'].'%')->join(', ') }}">
                        <defs>
                            <linearGradient id="areaGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#14b8a6" stop-opacity=".28" />
                                <stop offset="1" stop-color="#14b8a6" stop-opacity="0" />
                            </linearGradient>
                        </defs>
                        @foreach ([0.25, 0.5, 0.75] as $guide)
                            <line x1="0" x2="{{ \App\Livewire\Dashboard::CHART_WIDTH }}" y1="{{ \App\Livewire\Dashboard::CHART_HEIGHT * $guide }}" y2="{{ \App\Livewire\Dashboard::CHART_HEIGHT * $guide }}" stroke="#0d1b3d" stroke-opacity=".06" stroke-dasharray="4 6" />
                        @endforeach
                        <path class="fade-area" d="{{ $chart['area'] }}" fill="url(#areaGradient)" />
                        <path class="draw-line" pathLength="1" d="{{ $chart['line'] }}" fill="none" stroke="#0d9488" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                    </svg>
                    @foreach ($chart['points'] as $point)
                        <span class="fade-area absolute -translate-x-1/2 -translate-y-1/2 rounded-full border-[3px] bg-white {{ $loop->last ? 'size-4 border-sun-400' : 'size-2.5 border-brand-600' }}"
                              style="left: {{ $point['x'] / $width * 100 }}%; top: {{ $point['y'] / $height * 100 }}%"
                              title="{{ $point['title'] }}: {{ $point['value'] }}%"></span>
                    @endforeach
                    <span class="fade-area absolute -translate-x-1/2 rounded-full bg-navy-600 px-2.5 py-1 text-xs font-extrabold text-white shadow-lg"
                          style="left: {{ $last['x'] / $width * 100 }}%; top: calc({{ $last['y'] / $height * 100 }}% - 38px)">{{ round($last['value']) }}%</span>
                    </div>
                    <div class="mt-2 flex justify-between text-[11px] text-subtle">
                        @foreach ($chart['points'] as $point)
                            <span>{{ $point['label'] }}</span>
                        @endforeach
                    </div>
                @else
                    <div class="mt-4 grid h-48 place-items-center rounded-2xl bg-canvas text-center">
                        <div>
                            <svg class="mx-auto h-16 w-40 text-brand-500/40" viewBox="0 0 160 60" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path class="draw-line" pathLength="1" d="M5 50 C35 50 40 20 70 28 S110 50 155 10" /></svg>
                            <p class="mt-2 text-sm text-muted">Grafik muncul setelah kamu menyelesaikan minimal dua try out.</p>
                        </div>
                    </div>
                @endif
            </section>

            @if ($nextPackage)
                <section class="rise shine glass-dark relative flex flex-col overflow-hidden rounded-2xl p-5 text-white" style="--i:6">
                    <svg class="float-soft pointer-events-none absolute -top-3 -right-4 h-32 w-32 opacity-90" viewBox="0 0 120 120" aria-hidden="true">
                        <path d="M60 22 112 46 60 70 8 46Z" fill="#14b8a6" />
                        <path d="M30 57v22c0 9 13 16 30 16s30-7 30-16V57L60 70Z" fill="#0d9488" />
                        <path d="M60 46 96 52v28" stroke="#ffc23d" stroke-width="3.5" fill="none" stroke-linecap="round" />
                        <circle cx="96" cy="86" r="6" fill="#ffc23d" />
                    </svg>
                    <span class="w-fit rounded-full bg-white/10 px-3 py-1 text-[11px] font-bold tracking-wide text-sun-400 uppercase">{{ $runningAttempt ? 'Sedang berjalan' : 'Try out berikutnya' }}</span>
                    <h2 class="mt-3 max-w-[75%] text-xl leading-snug font-extrabold">{{ $runningAttempt?->package->title ?? $nextPackage->title }}</h2>
                    <dl class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-white/10 p-3"><dt class="text-[11px] text-white/60">Soal</dt><dd class="text-lg font-extrabold">{{ $nextPackage->sections->sum('questions_count') }}</dd></div>
                        <div class="rounded-xl bg-white/10 p-3"><dt class="text-[11px] text-white/60">Durasi</dt><dd class="text-lg font-extrabold">{{ $nextPackage->duration_minutes }} mnt</dd></div>
                    </dl>
                    <div class="mt-auto pt-5">
                        @if ($runningAttempt)
                            <a href="{{ route('exam.show', $runningAttempt) }}" class="btn w-full bg-sun-400 text-ink hover:bg-sun-400/90">Lanjutkan ujian</a>
                        @else
                            <a href="{{ route('tryout.show', $nextPackage) }}" wire:navigate class="btn w-full bg-white text-navy-700 hover:bg-white/90">
                                {{ $nextPackage->is_free ? 'Coba demo gratis' : 'Lihat paket' }} <x-icon name="arrow-right" class="size-4" />
                            </a>
                        @endif
                    </div>
                </section>
            @endif
        </div>

        {{-- Study plan / suggestions --}}
        <section class="rise" style="--i:7">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="border-l-4 border-brand-500 pl-3 font-bold">{{ $studyPlan->isNotEmpty() ? 'Rencana belajarmu' : 'Try out untukmu' }}</h2>
                <a href="{{ $studyPlan->isNotEmpty() ? route('results.show', $recentAttempts->first()) : route('tryout.index') }}" wire:navigate class="text-xs font-semibold text-brand-700 hover:underline">Lihat semua</a>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                @if ($studyPlan->isNotEmpty())
                    @foreach ($studyPlan as $index => $step)
                        @php $highlight = $index === 1; @endphp
                        <article @class(['stat-card flex items-start gap-3', 'bg-gradient-to-br from-brand-500 to-brand-700 text-white border-transparent' => $highlight])>
                            <div class="min-w-0 flex-1">
                                <span @class(['inline-block rounded-full px-2.5 py-0.5 text-[11px] font-bold', 'bg-white/20' => $highlight, 'bg-brand-50 text-brand-700' => ! $highlight])>{{ $step['day'] }}</span>
                                <p @class(['mt-2 text-sm font-semibold leading-snug', 'text-white/95' => $highlight])>{{ $step['task'] }}</p>
                            </div>
                            <span @class(['grid size-10 flex-none place-items-center rounded-full', 'bg-white/20' => $highlight, 'bg-canvas text-brand-700' => ! $highlight])><x-icon :name="['book', 'target', 'clipboard'][$index] ?? 'check'" class="size-5" /></span>
                        </article>
                    @endforeach
                @else
                    @foreach ($suggestedPackages->take(3) as $index => $package)
                        @php $highlight = $index === 1; @endphp
                        <a href="{{ route('tryout.show', $package) }}" wire:navigate @class(['stat-card flex items-start gap-3', 'bg-gradient-to-br from-brand-500 to-brand-700 text-white border-transparent' => $highlight])>
                            <div class="min-w-0 flex-1">
                                <p @class(['text-[11px] font-bold', 'text-white/80' => $highlight, 'text-subtle' => ! $highlight])>{{ $package->category->name }}</p>
                                <p class="mt-1 text-sm leading-snug font-bold">{{ $package->title }}</p>
                                <span @class(['mt-2 inline-block rounded-full px-2.5 py-0.5 text-[11px] font-bold', 'bg-white/20' => $highlight, 'bg-sun-100 text-sun-600' => ! $highlight && $package->is_free, 'bg-navy-50 text-muted' => ! $highlight && ! $package->is_free])>{{ $package->is_free ? 'Gratis' : $package->duration_minutes.' menit' }}</span>
                            </div>
                            <span @class(['grid size-10 flex-none place-items-center rounded-full', 'bg-white/20' => $highlight, 'bg-canvas text-brand-700' => ! $highlight])><x-icon name="arrow-up-right" class="size-5" /></span>
                        </a>
                    @endforeach
                @endif
            </div>
        </section>
    </div>

    {{-- ================= Right panel ================= --}}
    <aside class="glass-dark rise h-fit rounded-3xl p-5 text-white xl:sticky xl:top-5" style="--i:3" aria-label="Profil dan aktivitas">
        <div class="flex items-center gap-3">
            <span class="grid size-11 place-items-center rounded-full bg-gradient-to-br from-brand-500 to-sky-500 text-lg font-extrabold">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 1)) }}</span>
            <div class="min-w-0 flex-1">
                <p class="truncate font-bold">{{ $user->publicName() }}</p>
                <p class="truncate text-xs text-white/60">{{ $user->targetCategory ? 'Persiapan '.$user->targetCategory->name : 'Belum memilih tujuan' }}</p>
            </div>
            <a href="{{ route('profile.edit') }}" wire:navigate class="grid size-8 place-items-center rounded-full bg-white/10 hover:bg-white/20" aria-label="Pengaturan profil"><x-icon name="more" class="size-4" /></a>
        </div>

        <dl class="mt-5 grid grid-cols-3 divide-x divide-white/10 rounded-2xl bg-white/5 py-3 text-center">
            <div><dd class="text-base font-extrabold"><span x-data="countUp({{ $stats['points'] }})" x-text="display">{{ $stats['points'] }}</span></dd><dt class="text-[11px] text-white/55">Poin</dt></div>
            <div><dd class="text-base font-extrabold">{{ $rank ? '#'.$rank['rank'] : '–' }}</dd><dt class="text-[11px] text-white/55">Peringkat</dt></div>
            <div><dd class="text-base font-extrabold">{{ $stats['attempts'] }}</dd><dt class="text-[11px] text-white/55">Try out</dt></div>
        </dl>

        @if ($subscription)
            <p class="mt-4 rounded-xl bg-brand-500/15 px-3 py-2 text-xs text-brand-100"><strong class="text-white">{{ $subscription->plan->name }}</strong> · aktif {{ $subscription->daysLeft() }} hari lagi</p>
        @else
            <a href="{{ route('plans.index') }}" wire:navigate class="mt-4 flex items-center justify-between rounded-xl bg-sun-400 px-3 py-2.5 text-xs font-bold text-ink hover:bg-sun-400/90">Buka semua materi & try out <x-icon name="arrow-right" class="size-4" /></a>
        @endif

        {{-- Calendar --}}
        <section class="mt-6" aria-label="Kalender belajar">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold">{{ $calendar['title'] }}</h2>
                <span class="text-[11px] text-white/55">{{ $calendar['activeDays'] }} hari belajar</span>
            </div>
            <div class="mt-3 grid grid-cols-7 gap-y-1.5 text-center text-[11px]">
                @foreach ($weekdays as $weekday)
                    <span class="pb-1 font-semibold text-white/40">{{ $weekday }}</span>
                @endforeach
                @foreach ($calendar['weeks'] as $week)
                    @foreach ($week as $cell)
                        <span class="grid place-items-center">
                            @if ($cell['day'])
                                <span @class([
                                    'grid size-7 place-items-center rounded-full font-semibold transition',
                                    'bg-brand-500 text-white shadow shadow-brand-500/40' => $cell['active'],
                                    'ring-2 ring-sun-400 text-sun-400' => $cell['today'] && ! $cell['active'],
                                    'text-white/70' => ! $cell['active'] && ! $cell['today'],
                                ]) @if ($cell['active']) title="Ada aktivitas belajar" @endif>{{ $cell['day'] }}</span>
                            @endif
                        </span>
                    @endforeach
                @endforeach
            </div>
        </section>

        {{-- Recent activity --}}
        <section class="mt-6">
            <div class="flex items-center justify-between">
                <h2 class="border-l-4 border-brand-500 pl-2.5 text-sm font-bold">Aktivitas terbaru</h2>
                <a href="{{ route('tryout.index') }}" wire:navigate class="text-[11px] font-semibold text-white/60 hover:text-white">Lihat semua</a>
            </div>
            <ul class="mt-3 space-y-2.5">
                @forelse ($recentAttempts->take(4) as $attempt)
                    <li wire:key="activity-{{ $attempt->id }}">
                        <a href="{{ route('results.show', $attempt) }}" wire:navigate class="flex items-center gap-3 rounded-2xl bg-white/5 p-2.5 transition hover:bg-white/10">
                            <span @class(['grid size-10 flex-none place-items-center rounded-xl', 'bg-brand-500/20 text-brand-100' => $attempt->passed !== false, 'bg-coral-500/20 text-coral-100' => $attempt->passed === false])><x-icon name="clipboard" class="size-5" /></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-xs font-bold">{{ $attempt->package->title }}</span>
                                <span class="block text-[11px] text-white/50">{{ $attempt->submitted_at->setTimezone('Asia/Jakarta')->translatedFormat('d M, H:i') }}</span>
                            </span>
                            <span class="rounded-full bg-white/10 px-2 py-0.5 text-[11px] font-bold">{{ round($attempt->percentage()) }}%</span>
                        </a>
                    </li>
                @empty
                    <li class="rounded-2xl bg-white/5 p-4 text-xs text-white/60">Belum ada aktivitas. Mulai dari demo try out gratis!</li>
                @endforelse
            </ul>
        </section>
    </aside>
</div>
