<div class="space-y-6">
    <div class="rise" style="--i:0">
        <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Hasil peserta</h1>
        <p class="mt-1 text-sm text-muted">Pantau setiap pengerjaan try out dan latihan: nilai, kelulusan, durasi, dan aktivitas pindah tab.</p>
    </div>

    <section class="grid grid-cols-2 gap-4 lg:grid-cols-4" aria-label="Ringkasan hasil">
        @foreach ([
            ['Pengerjaan', number_format($summary['total'], 0, ',', '.'), 'clipboard', 'bg-navy-50 text-navy-600'],
            ['Rata-rata nilai', $summary['average'] !== null ? $summary['average'].'%' : '–', 'chart', 'bg-brand-50 text-brand-700'],
            ['Tingkat kelulusan', $summary['passRate'] !== null ? $summary['passRate'].'%' : '–', 'target', 'bg-sun-100 text-sun-600'],
            ['Sedang berjalan', number_format($summary['running'], 0, ',', '.'), 'clock', 'bg-coral-100 text-coral-600'],
        ] as $index => [$label, $value, $icon, $tone])
            <article class="stat-card rise" style="--i: {{ $index + 1 }}">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-xs font-bold text-subtle">{{ $label }}</h2>
                    <span class="grid size-8 place-items-center rounded-lg {{ $tone }}"><x-icon :name="$icon" class="size-4" /></span>
                </div>
                <p class="mt-3 text-2xl font-extrabold tracking-tight">{{ $value }}</p>
            </article>
        @endforeach
    </section>

    @if ($topPackages->isNotEmpty())
        <p class="text-xs text-muted">Paling banyak dikerjakan (30 hari):
            @foreach ($topPackages as $top)
                <button type="button" wire:click="$set('package', '{{ $top->id }}')" class="ml-1 rounded-full bg-white px-2.5 py-1 font-semibold text-ink hover:text-brand-700">{{ $top->title }} · {{ $top->total }}×</button>
            @endforeach
        </p>
    @endif

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
        <input type="search" wire:model.live.debounce.400ms="search" class="field lg:col-span-3 2xl:col-span-1" placeholder="Nama atau email peserta" aria-label="Cari peserta">
        <select wire:model.live="category" class="field" aria-label="Kategori">
            <option value="">Semua kategori</option>
            @foreach ($categories as $item) <option value="{{ $item->id }}">{{ $item->name }}</option> @endforeach
        </select>
        <select wire:model.live="package" class="field" aria-label="Paket">
            <option value="">Semua paket</option>
            @foreach ($packages as $item) <option value="{{ $item->id }}">{{ $item->is_practice ? '↳ ' : '' }}{{ $item->title }}</option> @endforeach
        </select>
        <select wire:model.live="result" class="field" aria-label="Hasil">
            <option value="">Semua hasil</option>
            <option value="lolos">Lolos ambang batas</option>
            <option value="belum">Belum lolos</option>
        </select>
        <select wire:model.live="status" class="field" aria-label="Status">
            <option value="">Semua status</option>
            <option value="selesai">Selesai</option>
            <option value="berjalan">Sedang berjalan</option>
        </select>
        <select wire:model.live="period" class="field" aria-label="Periode">
            <option value="1">24 jam terakhir</option>
            <option value="7">7 hari terakhir</option>
            <option value="30">30 hari terakhir</option>
            <option value="semua">Semua waktu</option>
        </select>
    </div>

    <div class="stat-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead><tr><th>Peserta</th><th>Paket</th><th>Nilai</th><th>Hasil</th><th>Durasi</th><th>Tanggal</th><th></th></tr></thead>
                <tbody>
                    @forelse ($attempts as $attempt)
                        <tr wire:key="attempt-{{ $attempt->id }}">
                            <td>
                                <span class="font-semibold">{{ $attempt->user->name }}</span>
                                <span class="block text-xs text-subtle">{{ $attempt->user->email }}</span>
                            </td>
                            <td>
                                <span class="font-semibold">{{ $attempt->package->title }}</span>
                                <span class="block text-xs text-subtle">{{ $attempt->package->category->name }}{{ $attempt->package->is_practice ? ' · latihan topik' : '' }}{{ $attempt->is_first_attempt ? '' : ' · percobaan ulang' }}</span>
                            </td>
                            <td>
                                @if ($attempt->isInProgress())
                                    <span class="text-subtle">–</span>
                                @else
                                    <span class="font-bold">{{ $attempt->percentage() }}%</span>
                                    <span class="block text-xs text-subtle">{{ $attempt->correct_count }} benar · {{ $attempt->wrong_count }} salah · {{ $attempt->blank_count }} kosong</span>
                                @endif
                            </td>
                            <td>
                                @if ($attempt->isInProgress())
                                    <span class="badge bg-sun-100 text-sun-600">Berjalan</span>
                                @elseif ($attempt->passed === true)
                                    <span class="badge bg-brand-50 text-brand-700">Lolos</span>
                                @elseif ($attempt->passed === false)
                                    <span class="badge bg-coral-100 text-coral-600">Belum lolos</span>
                                @else
                                    <span class="badge bg-navy-50 text-muted">Selesai</span>
                                @endif
                                @if ($attempt->tab_switches >= 3)
                                    <span class="badge mt-1 bg-coral-100 text-coral-600" title="Peserta meninggalkan tab ujian">⚠ pindah tab {{ $attempt->tab_switches }}×</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-muted">{{ $attempt->isInProgress() ? '–' : $attempt->durationMinutes().' mnt' }}</td>
                            <td class="whitespace-nowrap text-xs text-muted">{{ $attempt->started_at->setTimezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }}</td>
                            <td class="text-right"><a href="{{ route('admin.attempts.show', $attempt) }}" wire:navigate class="btn btn-ghost btn-sm">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-12 text-center text-muted">Tidak ada pengerjaan untuk filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div>{{ $attempts->links() }}</div>
</div>
