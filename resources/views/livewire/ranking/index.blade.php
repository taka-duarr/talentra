@php
    $types = ['global' => 'Global', 'kategori' => 'Per kategori', 'paket' => 'Per try out'];
    $format = fn ($value) => rtrim(rtrim(number_format((float) $value, 1, ',', '.'), '0'), ',');
    $displayName = fn ($row) => $row['user_id'] === auth()->id() ? 'Kamu' : ($row['name'] ?? 'Peserta anonim');
    $podium = $board->take(3);
@endphp
<div>
    <x-page-header title="Ranking" description="Bandingkan perkembanganmu dengan peserta lain. Hanya percobaan pertama yang dihitung untuk ranking try out." />

    <div class="grid gap-6 lg:grid-cols-[1fr_300px]">
        <div>
            <div class="mb-4 flex flex-wrap items-center gap-2" role="tablist" aria-label="Jenis ranking">
                @foreach ($types as $key => $label)
                    <button type="button" wire:key="type-{{ $key }}" wire:click="$set('type', '{{ $key }}')" role="tab" aria-selected="{{ $type === $key ? 'true' : 'false' }}"
                            @class(['btn btn-sm', 'btn-navy' => $type === $key, 'btn-outline' => $type !== $key])>{{ $label }}</button>
                @endforeach

                <div class="ml-auto flex flex-wrap gap-2">
                    @if ($type === 'kategori')
                        <select wire:model.live="category" class="field w-auto py-1.5" aria-label="Kategori">
                            @foreach ($categories as $item) <option value="{{ $item->slug }}">{{ $item->name }}</option> @endforeach
                        </select>
                    @endif
                    @if ($type === 'paket')
                        <select wire:model.live="package" class="field w-auto py-1.5" aria-label="Paket try out">
                            @forelse ($packages as $item) <option value="{{ $item->slug }}">{{ $item->title }}</option> @empty <option value="">Belum ada paket ranking</option> @endforelse
                        </select>
                    @else
                        <select wire:model.live="period" class="field w-auto py-1.5" aria-label="Periode">
                            @foreach ($periods as $key => $label) <option value="{{ $key }}">{{ $label }}</option> @endforeach
                        </select>
                    @endif
                </div>
            </div>

            @if ($board->isEmpty())
                <div class="card p-10 text-center text-muted">
                    <p class="font-semibold text-ink">Belum ada data ranking untuk pilihan ini.</p>
                    <p class="mt-1 text-sm">Kerjakan try out atau selesaikan materi untuk mengumpulkan poin.</p>
                </div>
            @else
                <div class="mb-6 grid grid-cols-3 items-end gap-3">
                    @foreach ([1, 0, 2] as $index)
                        @php $row = $podium->get($index); @endphp
                        @if ($row)
                            <div @class(['card p-4 text-center', 'pb-8 border-sun-400' => $index === 0, 'ring-2 ring-brand-500' => $row['user_id'] === auth()->id()])>
                                <p class="text-2xl" aria-hidden="true">{{ ['🥇', '🥈', '🥉'][$index] }}</p>
                                <p class="mt-1 truncate text-sm font-bold">{{ $displayName($row) }}</p>
                                <p class="text-lg font-extrabold text-navy-600">{{ $format($row['score']) }}<span class="text-xs font-semibold text-subtle"> {{ $unit }}</span></p>
                            </div>
                        @else
                            <div></div>
                        @endif
                    @endforeach
                </div>

                <ol class="card divide-y divide-line overflow-hidden">
                    @foreach ($board as $row)
                        <li wire:key="row-{{ $row['user_id'] }}" @class(['flex items-center gap-4 px-5 py-3 text-sm', 'bg-brand-50' => $row['user_id'] === auth()->id()])>
                            <span class="w-8 text-right font-extrabold text-subtle">{{ $row['rank'] }}</span>
                            <span class="flex-1 font-semibold">{{ $displayName($row) }}</span>
                            <span class="hidden text-xs text-subtle sm:inline">{{ $row['detail'] }}</span>
                            <span class="w-24 text-right font-bold">{{ $format($row['score']) }} <span class="text-xs font-normal text-subtle">{{ $unit }}</span></span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>

        <aside class="space-y-4">
            <section class="card p-5">
                <h2 class="text-sm font-bold text-subtle">Posisimu</h2>
                @if ($position)
                    <p class="mt-2 text-3xl font-extrabold">#{{ $position['rank'] }}<span class="text-base font-semibold text-subtle"> dari {{ number_format($position['total'], 0, ',', '.') }}</span></p>
                    <p class="text-sm text-muted">Lebih baik dari {{ $position['percentile'] }}% peserta · {{ $format($myRow['score']) }} {{ $unit }}</p>
                @else
                    <p class="mt-2 text-sm text-muted">Kamu belum masuk ranking ini. @if ($type === 'paket') Kerjakan paketnya untuk masuk. @else Kumpulkan poin dengan mengerjakan try out. @endif</p>
                @endif
                @if (auth()->user()->hide_from_ranking)
                    <p class="mt-3 rounded-lg bg-canvas px-3 py-2 text-xs text-muted">Namamu disembunyikan dari peserta lain. Ubah di <a href="{{ route('profile.edit') }}" wire:navigate class="font-semibold text-brand-700">profil</a>.</p>
                @endif
            </section>

            <section class="card p-5">
                <h2 class="text-sm font-bold text-subtle">Poin & badge</h2>
                <p class="mt-2 text-3xl font-extrabold">{{ number_format($totalPoints, 0, ',', '.') }} <span class="text-base font-semibold text-subtle">poin</span></p>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @forelse ($badges as $badge)
                        <span class="badge bg-sun-100 text-sun-600">🏆 {{ $badge }}</span>
                    @empty
                        <p class="text-xs text-muted">Masuk 10 besar mingguan atau 100 besar kategori tujuanmu untuk mendapat badge.</p>
                    @endforelse
                </div>
                <dl class="mt-4 space-y-1 border-t border-line pt-3 text-xs text-muted">
                    <div class="flex justify-between"><dt>Selesai try out (pertama)</dt><dd class="font-semibold">+{{ config('talentra.points.tryout_completed') }}</dd></div>
                    <div class="flex justify-between"><dt>Lolos ambang batas</dt><dd class="font-semibold">+{{ config('talentra.points.tryout_passed') }}</dd></div>
                    <div class="flex justify-between"><dt>Selesai satu bab materi</dt><dd class="font-semibold">+{{ config('talentra.points.chapter_completed') }}</dd></div>
                    <div class="flex justify-between"><dt>Jawaban terbaik di forum</dt><dd class="font-semibold">+{{ config('talentra.points.best_answer') }}</dd></div>
                </dl>
            </section>
        </aside>
    </div>
</div>
