@php use App\Support\Money; $maxRevenue = max(1, $revenueChart->max('value')); @endphp
<div class="space-y-6">
    <x-page-header title="Ringkasan" description="Kondisi Talentra hari ini." />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @if ($isSuper)
            <a href="{{ route('admin.orders.index') }}" wire:navigate class="card p-5 hover:border-brand-500">
                <p class="text-xs font-bold text-subtle">Pesanan perlu verifikasi</p>
                <p @class(['mt-2 text-3xl font-extrabold', 'text-coral-600' => $stats['pendingOrders'] > 0])>{{ $stats['pendingOrders'] }}</p>
            </a>
            <div class="card p-5"><p class="text-xs font-bold text-subtle">Pendapatan bulan ini</p><p class="mt-2 text-2xl font-extrabold">{{ Money::rupiah($stats['revenueThisMonth']) }}</p></div>
        @endif
        <div class="card p-5"><p class="text-xs font-bold text-subtle">Pelanggan aktif</p><p class="mt-2 text-3xl font-extrabold">{{ $stats['activeSubscribers'] }}</p></div>
        <div class="card p-5"><p class="text-xs font-bold text-subtle">Pengguna baru (30 hari)</p><p class="mt-2 text-3xl font-extrabold">{{ $stats['newUsers'] }}</p></div>
        <div class="card p-5"><p class="text-xs font-bold text-subtle">Try out selesai hari ini</p><p class="mt-2 text-3xl font-extrabold">{{ $stats['attemptsToday'] }}</p></div>
        <a href="{{ route('admin.questions.index', ['status' => 'review']) }}" wire:navigate class="card p-5 hover:border-brand-500">
            <p class="text-xs font-bold text-subtle">Soal menunggu review</p>
            <p class="mt-2 text-3xl font-extrabold">{{ $stats['questionsInReview'] }}</p>
        </a>
        <div class="card p-5"><p class="text-xs font-bold text-subtle">Analisis AI hari ini</p><p class="mt-2 text-3xl font-extrabold">{{ $stats['aiAnalysesToday'] }}</p></div>
        <div class="card p-5"><p class="text-xs font-bold text-subtle">Token AI bulan ini</p><p class="mt-2 text-3xl font-extrabold">{{ number_format($stats['aiTokensMonth'], 0, ',', '.') }}</p></div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        @if ($isSuper)
            <section class="card p-6">
                <h2 class="font-bold">Pendapatan 6 bulan terakhir</h2>
                <div class="mt-6 flex h-48 items-end gap-3" role="img" aria-label="Grafik pendapatan bulanan">
                    @foreach ($revenueChart as $bar)
                        <div class="flex flex-1 flex-col items-center gap-2" title="{{ Money::rupiah($bar['value']) }}">
                            <div class="w-full max-w-14 rounded-t-lg bg-navy-600" style="height: {{ max(2, $bar['value'] / $maxRevenue * 100) }}%"></div>
                            <span class="text-xs text-subtle">{{ $bar['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="card overflow-hidden">
            <h2 class="border-b border-line px-5 py-4 font-bold">Soal paling sering salah</h2>
            <ul class="divide-y divide-line">
                @forelse ($hardestQuestions as $row)
                    <li class="flex items-center gap-4 px-5 py-3 text-sm">
                        <span class="line-clamp-1 flex-1">{{ \Illuminate\Support\Str::limit(strip_tags($row->body), 90) }}</span>
                        <span class="font-bold text-coral-600">{{ round($row->correct / $row->total * 100) }}% benar</span>
                        <a href="{{ route('admin.questions.edit', $row->question_id) }}" wire:navigate class="text-xs font-semibold text-brand-700">Periksa</a>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-muted">Data muncul setelah setiap soal dikerjakan minimal 3 kali.</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
