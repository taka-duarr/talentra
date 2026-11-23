@php
    use App\Support\Money;
    $maxRevenue = max(1, $revenueChart->max('value'));
    $cards = array_values(array_filter([
        $isSuper ? ['Pendapatan bulan ini', $stats['revenueThisMonth'], 'receipt', 'bg-brand-50 text-brand-700', true, null] : null,
        $isSuper ? ['Pesanan perlu verifikasi', $stats['pendingOrders'], 'card', 'bg-sun-100 text-sun-600', false, route('admin.orders.index')] : null,
        ['Pelanggan aktif', $stats['activeSubscribers'], 'users', 'bg-sky-100 text-sky-600', false, null],
        ['Pengguna baru (30 hari)', $stats['newUsers'], 'user', 'bg-navy-50 text-navy-600', false, null],
        ['Try out selesai hari ini', $stats['attemptsToday'], 'clipboard', 'bg-coral-100 text-coral-600', false, route('admin.attempts.index', ['periode' => '1'])],
        ['Soal menunggu review', $stats['questionsInReview'], 'question', 'bg-sun-100 text-sun-600', false, route('admin.questions.index', ['status' => 'review'])],
        ['Laporan forum terbuka', $stats['openReports'], 'flag', 'bg-coral-100 text-coral-600', false, route('admin.moderation')],
        ['Analisis AI hari ini', $stats['aiAnalysesToday'], 'spark', 'bg-brand-50 text-brand-700', false, null],
    ]));
@endphp
<div class="space-y-6">
    <div class="rise" style="--i:0">
        <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Ringkasan</h1>
        <p class="mt-1 text-sm text-muted">Kondisi Talentra hari ini.</p>
    </div>

    <section class="grid grid-cols-2 gap-4 lg:grid-cols-4" aria-label="Statistik">
        @foreach ($cards as $index => [$label, $value, $icon, $tone, $isMoney, $url])
            <{{ $url ? 'a' : 'article' }} @if ($url) href="{{ $url }}" wire:navigate @endif class="stat-card rise" style="--i: {{ $index + 1 }}">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-xs font-bold text-subtle">{{ $label }}</h2>
                    <span class="grid size-8 flex-none place-items-center rounded-lg {{ $tone }}"><x-icon :name="$icon" class="size-4" /></span>
                </div>
                <p @class(['mt-3 text-2xl font-extrabold tracking-tight', 'text-coral-600' => $value > 0 && in_array($label, ['Pesanan perlu verifikasi', 'Soal menunggu review', 'Laporan forum terbuka'], true)])>
                    @if ($isMoney)<span class="text-sm font-bold text-subtle">Rp</span>@endif<span x-data="countUp({{ $value }})" x-text="display">{{ number_format($value, 0, ',', '.') }}</span>
                </p>
            </{{ $url ? 'a' : 'article' }}>
        @endforeach
    </section>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="stat-card rise overflow-hidden p-0" style="--i:9">
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 class="font-bold">Hasil terbaru</h2>
                <a href="{{ route('admin.attempts.index') }}" wire:navigate class="text-xs font-semibold text-brand-700 hover:underline">Lihat semua hasil</a>
            </div>
            <ul class="divide-y divide-line">
                @forelse ($latestAttempts as $attempt)
                    <li wire:key="latest-{{ $attempt->id }}">
                        <a href="{{ route('admin.attempts.show', $attempt) }}" wire:navigate class="flex items-center gap-3 px-5 py-3 text-sm hover:bg-canvas">
                            <span class="grid size-9 flex-none place-items-center rounded-full bg-navy-600 text-xs font-bold text-white">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($attempt->user->name, 0, 1)) }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-semibold">{{ $attempt->user->name }}</span>
                                <span class="block truncate text-xs text-subtle">{{ $attempt->package->title }} · {{ $attempt->submitted_at->setTimezone('Asia/Jakarta')->translatedFormat('d M, H:i') }}</span>
                            </span>
                            @if ($attempt->tab_switches >= 3) <span class="text-xs text-coral-600" title="Pindah tab {{ $attempt->tab_switches }}×">⚠</span> @endif
                            <span @class(['rounded-full px-2.5 py-0.5 text-xs font-bold', 'bg-brand-50 text-brand-700' => $attempt->passed !== false, 'bg-coral-100 text-coral-600' => $attempt->passed === false])>{{ round($attempt->percentage()) }}%</span>
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-muted">Belum ada try out yang diselesaikan.</li>
                @endforelse
            </ul>
        </section>

        @if ($isSuper)
            <section class="stat-card rise p-5" style="--i:10">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold">Pendapatan 6 bulan terakhir</h2>
                    <span class="rounded-full bg-canvas px-3 py-1 text-xs font-semibold text-muted">Rp{{ number_format($revenueChart->sum('value'), 0, ',', '.') }}</span>
                </div>
                <div class="mt-6 flex h-56 items-end gap-3" role="img" aria-label="Grafik pendapatan: {{ $revenueChart->map(fn ($bar) => $bar['label'].' '.Money::rupiah($bar['value']))->join(', ') }}">
                    @foreach ($revenueChart as $index => $bar)
                        <div class="group flex h-full flex-1 flex-col items-center justify-end gap-2">
                            <span class="rounded-md bg-navy-600 px-1.5 py-0.5 text-[10px] font-bold text-white opacity-0 transition group-hover:opacity-100">{{ Money::rupiahShort($bar['value']) }}</span>
                            <div @class(['grow-bar w-full max-w-14 rounded-xl', 'bg-gradient-to-t from-brand-600 to-brand-500' => $loop->last, 'bg-gradient-to-t from-navy-100 to-navy-50 group-hover:from-brand-100 group-hover:to-brand-50' => ! $loop->last])
                                 style="height: {{ max(4, $bar['value'] / $maxRevenue * 100) }}%; --i: {{ $index }}"></div>
                            <span class="text-xs text-subtle">{{ $bar['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="stat-card rise overflow-hidden p-0 {{ $isSuper ? 'xl:col-span-2' : '' }}" style="--i:11">
            <h2 class="border-b border-line px-5 py-4 font-bold">Soal paling sering salah</h2>
            <ul class="divide-y divide-line">
                @forelse ($hardestQuestions as $row)
                    @php $rate = round($row->correct / $row->total * 100); @endphp
                    <li class="flex items-center gap-4 px-5 py-3 text-sm">
                        <span class="line-clamp-1 flex-1">{{ \Illuminate\Support\Str::limit(strip_tags($row->body), 110) }}</span>
                        <span class="w-24">
                            <span class="block h-1.5 overflow-hidden rounded-full bg-coral-100"><span class="fill-bar block h-full rounded-full bg-coral-500" style="width: {{ $rate }}%"></span></span>
                            <span class="mt-1 block text-[11px] font-bold text-coral-600">{{ $rate }}% benar</span>
                        </span>
                        <a href="{{ route('admin.questions.edit', $row->question_id) }}" wire:navigate class="text-xs font-semibold text-brand-700">Periksa</a>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-muted">Data muncul setelah setiap soal dikerjakan minimal 3 kali.</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
