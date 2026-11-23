<div>
    <x-page-header title="Ranking" description="Ranking dihitung ulang otomatis setiap {{ config('talentra.ranking.cache_minutes') }} menit. Peserta yang dikeluarkan tidak muncul di semua ranking.">
        <x-slot:actions>
            <button type="button" wire:click="refresh" class="btn btn-outline">Hitung ulang sekarang</button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="card overflow-hidden">
            <h2 class="border-b border-line px-5 py-4 font-bold">Perlu diperiksa</h2>
            <p class="px-5 pt-3 text-xs text-subtle">Percobaan pertama dengan banyak pindah tab (≥5) atau selesai terlalu cepat dibanding jumlah jawaban benar.</p>
            <ul class="mt-2 divide-y divide-line text-sm">
                @forelse ($suspicious as $attempt)
                    <li wire:key="sus-{{ $attempt->id }}" class="flex items-center gap-3 px-5 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold">{{ $attempt->user->name }} <span class="text-xs font-normal text-subtle">{{ $attempt->package->title }}</span></p>
                            <p class="text-xs text-muted">{{ $attempt->percentage() }}% · {{ $attempt->started_at->diffInMinutes($attempt->submitted_at) }} menit · pindah tab {{ $attempt->tab_switches }}×</p>
                        </div>
                        <button type="button" wire:click="toggleExclusion({{ $attempt->user_id }})" class="btn btn-ghost btn-sm {{ $attempt->user->isExcludedFromRanking() ? '' : 'text-coral-600' }}">
                            {{ $attempt->user->isExcludedFromRanking() ? 'Kembalikan' : 'Keluarkan' }}
                        </button>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-muted">Tidak ada yang mencurigakan.</li>
                @endforelse
            </ul>
        </section>

        <section class="card overflow-hidden">
            <h2 class="border-b border-line px-5 py-4 font-bold">10 besar global minggu ini</h2>
            <ol class="divide-y divide-line text-sm">
                @forelse ($topWeekly as $row)
                    <li class="flex items-center gap-3 px-5 py-2.5">
                        <span class="w-6 font-bold text-subtle">{{ $row['rank'] }}</span>
                        <span class="flex-1">{{ $row['name'] ?? 'Peserta anonim (ID '.$row['user_id'].')' }}</span>
                        <span class="font-bold">{{ (int) $row['score'] }} poin</span>
                        <button type="button" wire:click="toggleExclusion({{ $row['user_id'] }})" class="text-xs font-semibold text-coral-600">Keluarkan</button>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-muted">Belum ada poin minggu ini.</li>
                @endforelse
            </ol>
            <p class="border-t border-line px-5 py-3 text-xs text-subtle">Poin dibagikan hari ini: {{ $pointsToday }}</p>
        </section>

        <section class="card p-5">
            <h2 class="font-bold">Cari peserta</h2>
            <input type="search" wire:model.live.debounce.400ms="search" class="field mt-3" placeholder="Nama atau email" aria-label="Cari peserta">
            <ul class="mt-3 divide-y divide-line text-sm">
                @foreach ($searchResults as $user)
                    <li wire:key="search-{{ $user->id }}" class="flex items-center justify-between py-2.5">
                        <span>{{ $user->name }} <span class="text-xs text-subtle">{{ $user->email }}</span></span>
                        <button type="button" wire:click="toggleExclusion({{ $user->id }})" class="btn btn-ghost btn-sm">{{ $user->isExcludedFromRanking() ? 'Kembalikan' : 'Keluarkan' }}</button>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="card overflow-hidden">
            <h2 class="border-b border-line px-5 py-4 font-bold">Dikeluarkan dari ranking</h2>
            <ul class="divide-y divide-line text-sm">
                @forelse ($excluded as $user)
                    <li class="flex items-center justify-between px-5 py-2.5">
                        <span>{{ $user->name }} <span class="text-xs text-subtle">sejak {{ $user->ranking_excluded_at->translatedFormat('d M Y') }}</span></span>
                        <button type="button" wire:click="toggleExclusion({{ $user->id }})" class="btn btn-ghost btn-sm">Kembalikan</button>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-muted">Tidak ada.</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
