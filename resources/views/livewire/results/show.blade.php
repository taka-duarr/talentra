@php
    $formatScore = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
    $masteryLabel = fn (float $percentage) => collect($masteryLevels)->first(fn ($level) => $percentage >= $level['min'])['label'];
@endphp
<div class="space-y-6" @if ($analysisPending) wire:poll.3s @endif>
    <div>
        <a href="{{ route('tryout.show', $attempt->package) }}" wire:navigate class="text-sm font-semibold text-muted hover:text-ink">← {{ $attempt->package->title }}</a>
        <h1 class="mt-3 text-2xl font-extrabold tracking-tight sm:text-3xl">Hasil try out</h1>
        <p class="mt-1 text-sm text-muted">Diselesaikan {{ $attempt->submitted_at->translatedFormat('d F Y, H:i') }} · {{ $attempt->durationMinutes() }} dari {{ $attempt->package->duration_minutes }} menit</p>
    </div>

    {{-- Score summary --}}
    <section class="card grid gap-6 p-6 sm:grid-cols-[auto_1fr] sm:p-8">
        <div class="text-center sm:pr-8 sm:text-left">
            <p class="text-sm font-semibold text-subtle">Skor total</p>
            <p class="mt-1 text-5xl font-extrabold tracking-tight text-navy-600">{{ $formatScore($attempt->score) }}</p>
            <p class="text-sm text-muted">dari {{ $formatScore($attempt->max_score) }} · {{ $attempt->percentage() }}%</p>
            @if ($attempt->passed === true)
                <span class="badge mt-3 bg-brand-50 text-brand-700">Lolos ambang batas</span>
            @elseif ($attempt->passed === false)
                <span class="badge mt-3 bg-coral-100 text-coral-600">Belum lolos ambang batas</span>
            @endif
        </div>
        <div class="grid grid-cols-3 gap-3 self-center">
            <div class="rounded-xl bg-brand-50 p-4 text-center"><p class="text-xs font-semibold text-brand-700">Benar</p><p class="mt-1 text-2xl font-extrabold text-brand-700">{{ $attempt->correct_count }}</p></div>
            <div class="rounded-xl bg-coral-100 p-4 text-center"><p class="text-xs font-semibold text-coral-600">Salah</p><p class="mt-1 text-2xl font-extrabold text-coral-600">{{ $attempt->wrong_count }}</p></div>
            <div class="rounded-xl bg-navy-50 p-4 text-center"><p class="text-xs font-semibold text-muted">Kosong</p><p class="mt-1 text-2xl font-extrabold text-muted">{{ $attempt->blank_count }}</p></div>
            @if ($packagePosition && $attempt->is_first_attempt)
                <p class="col-span-3 text-center text-sm text-muted">Peringkat <strong class="text-ink">#{{ $packagePosition['rank'] }}</strong> dari {{ $packagePosition['total'] }} peserta paket ini. <a href="{{ route('ranking.index', ['jenis' => 'paket', 'paket' => $attempt->package->slug]) }}" wire:navigate class="font-semibold text-brand-700 hover:underline">Lihat ranking</a></p>
            @elseif ($percentile !== null)
                <p class="col-span-3 text-center text-sm text-muted">Skormu lebih baik dari <strong class="text-ink">{{ $percentile }}%</strong> peserta paket ini (percobaan pertama).</p>
            @elseif (! $attempt->is_first_attempt && $attempt->package->counts_for_ranking)
                <p class="col-span-3 text-center text-xs text-subtle">Ini bukan percobaan pertama, jadi tidak dihitung untuk ranking.</p>
            @endif
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Sections --}}
        <section class="card overflow-hidden">
            <h2 class="border-b border-line px-5 py-4 font-bold">Skor per bagian</h2>
            <ul class="divide-y divide-line">
                @foreach ($attempt->section_scores ?? [] as $section)
                    @php $ratio = $section['max_score'] > 0 ? min(100, $section['score'] / $section['max_score'] * 100) : 0; @endphp
                    <li class="px-5 py-4">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-semibold">{{ $section['name'] }}</span>
                            <span><strong>{{ $formatScore($section['score']) }}</strong> <span class="text-subtle">/ {{ $formatScore($section['max_score']) }}</span></span>
                        </div>
                        <div class="relative mt-2 h-2.5 overflow-hidden rounded-full bg-navy-50">
                            <div @class(['h-full rounded-full', 'bg-brand-500' => $section['passed'] !== false, 'bg-coral-500' => $section['passed'] === false]) style="width: {{ $ratio }}%"></div>
                            @if ($section['passing_score'] && $section['max_score'] > 0)
                                <span class="absolute inset-y-0 w-0.5 bg-ink" style="left: {{ min(100, $section['passing_score'] / $section['max_score'] * 100) }}%" title="Ambang batas {{ $section['passing_score'] }}"></span>
                            @endif
                        </div>
                        <p class="mt-1.5 text-xs text-subtle">
                            {{ $section['correct'] }} benar · {{ $section['wrong'] }} salah · {{ $section['blank'] }} kosong
                            @if ($section['passing_score']) · ambang batas {{ $section['passing_score'] }} {{ $section['passed'] ? '✓' : '✗' }} @endif
                        </p>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Topic mastery --}}
        <section class="card overflow-hidden">
            <h2 class="border-b border-line px-5 py-4 font-bold">Penguasaan per topik</h2>
            <ul class="divide-y divide-line">
                @foreach ($topics as $topic)
                    <li class="flex items-center gap-4 px-5 py-3.5">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold">{{ $topic['topic'] }}</p>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-navy-50">
                                <div @class(['h-full rounded-full', 'bg-brand-500' => $topic['percentage'] >= 70, 'bg-sun-400' => $topic['percentage'] >= 50 && $topic['percentage'] < 70, 'bg-coral-500' => $topic['percentage'] < 50]) style="width: {{ $topic['percentage'] }}%"></div>
                            </div>
                        </div>
                        <div class="w-28 text-right">
                            <p class="text-sm font-bold">{{ $topic['percentage'] }}%</p>
                            <p class="text-xs text-subtle">{{ $masteryLabel($topic['percentage']) }}</p>
                        </div>
                        @if ($topic['percentage'] < 70 && $practiceTopicIds->contains($topic['topic_id']))
                            @if ($canPractice)
                                <form method="POST" action="{{ route('practice.start', $topic['topic_id']) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline btn-sm whitespace-nowrap" title="Latihan 10 soal acak topik {{ $topic['topic'] }}">Latihan</button>
                                </form>
                            @else
                                <a href="{{ route('plans.index') }}" wire:navigate class="btn btn-ghost btn-sm whitespace-nowrap" title="Latihan per topik untuk pelanggan">🔒 Latihan</a>
                            @endif
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    {{-- AI analysis --}}
    <section class="card p-6 sm:p-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="flex items-center gap-2 text-lg font-extrabold">
                <svg class="size-5 text-sun-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9Z"/></svg>
                Analisis & rekomendasi
            </h2>
            @if ($analysis?->source === 'ai')
                <span class="text-xs text-subtle">Dibuat dengan bantuan AI dan dapat keliru.</span>
            @endif
        </div>

        @if ($analysisPending)
            <div class="mt-5 space-y-3" aria-busy="true" aria-label="Analisis sedang dibuat">
                <div class="h-4 w-3/4 animate-pulse rounded bg-navy-50"></div>
                <div class="h-4 w-2/3 animate-pulse rounded bg-navy-50"></div>
                <div class="h-4 w-1/2 animate-pulse rounded bg-navy-50"></div>
                <p class="text-sm text-muted">Sedang menganalisis hasilmu…</p>
            </div>
        @else
            @php $result = $analysis->result; @endphp
            <p class="mt-4 leading-7">{{ $result['summary'] }}</p>

            @if ($isDemoView)
                @if (! empty($result['weaknesses']))
                    <div class="mt-5 rounded-xl bg-coral-100/60 p-4">
                        <p class="text-sm font-bold text-coral-600">Kelemahan utama: {{ $result['weaknesses'][0]['topic'] }}</p>
                        <p class="mt-1 text-sm text-muted">{{ $result['weaknesses'][0]['reason'] }}</p>
                    </div>
                @endif
                <div class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-dashed border-navy-100 p-4">
                    <p class="text-sm text-muted">Rekomendasi lengkap dan rencana belajar 7 hari tersedia untuk pelanggan.</p>
                    <a href="{{ route('plans.index') }}" wire:navigate class="btn btn-primary btn-sm">Buka analisis lengkap</a>
                </div>
            @else
                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    <div>
                        <h3 class="text-sm font-bold text-brand-700">Kekuatan</h3>
                        <ul class="mt-2 space-y-1.5 text-sm">
                            @forelse ($result['strengths'] as $strength)
                                <li class="flex gap-2"><span class="text-brand-600">✓</span>{{ $strength }}</li>
                            @empty
                                <li class="text-muted">Belum ada topik dengan penguasaan tinggi. Terus berlatih!</li>
                            @endforelse
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-coral-600">Perlu ditingkatkan</h3>
                        <ul class="mt-2 space-y-2 text-sm">
                            @forelse ($result['weaknesses'] as $weakness)
                                <li><strong>{{ $weakness['topic'] }}</strong> <span class="text-muted">— {{ $weakness['reason'] }}</span></li>
                            @empty
                                <li class="text-muted">Tidak ada kelemahan menonjol.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                @if (! empty($result['recommendations']))
                    <h3 class="mt-8 text-sm font-bold">Rekomendasi belajar</h3>
                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                        @foreach ($result['recommendations'] as $recommendation)
                            @php
                                $topicRow = $topics->firstWhere('topic', $recommendation['topic']);
                                $material = $topicRow ? $firstMaterials->get($topicRow['chapter_id']) : null;
                            @endphp
                            <div class="rounded-xl bg-brand-50 p-4">
                                <p class="text-sm font-bold text-brand-700">{{ $recommendation['topic'] }}</p>
                                <p class="mt-1 text-sm text-muted">{{ $recommendation['action'] }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-3">
                                    @if ($material)
                                        <a href="{{ route('learn.material', $material) }}" wire:navigate class="text-sm font-semibold text-brand-700 hover:underline">Buka materi: {{ $material->title }} →</a>
                                    @endif
                                    @if ($topicRow && $practiceTopicIds->contains($topicRow['topic_id']))
                                        <form method="POST" action="{{ route('practice.start', $topicRow['topic_id']) }}">
                                            @csrf
                                            <button type="submit" class="text-sm font-semibold text-navy-600 hover:underline">Latihan 10 soal →</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if (! empty($result['study_plan']))
                    <h3 class="mt-8 text-sm font-bold">Rencana belajar</h3>
                    <ol class="mt-3 divide-y divide-line rounded-xl border border-line">
                        @foreach ($result['study_plan'] as $step)
                            <li class="flex gap-4 px-4 py-3 text-sm"><span class="w-16 flex-none font-bold text-navy-600">{{ $step['day'] }}</span><span class="text-muted">{{ $step['task'] }}</span></li>
                        @endforeach
                    </ol>
                @endif
            @endif

            @if ($analysis->error && $attempt->user_id === auth()->id())
                <button type="button" wire:click="retryAnalysis" wire:loading.attr="disabled" class="btn btn-ghost btn-sm mt-4">Coba analisis AI ulang</button>
            @endif
        @endif
    </section>

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('results.review', $attempt) }}" wire:navigate class="btn btn-navy">Lihat pembahasan soal</a>
        <a href="{{ route('tryout.index') }}" wire:navigate class="btn btn-outline">Try out lainnya</a>
    </div>
</div>
