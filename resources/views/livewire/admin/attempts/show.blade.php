@php
    $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
    $format = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
    $user = $attempt->user;
@endphp
<div class="space-y-6">
    <a href="{{ route('admin.attempts.index') }}" wire:navigate class="text-sm font-semibold text-muted hover:text-ink">← Hasil peserta</a>

    <div class="rise flex flex-wrap items-start justify-between gap-4" style="--i:0">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight">{{ $attempt->package->title }}</h1>
            <p class="mt-1 text-sm text-muted">
                {{ $user->name }} ({{ $user->email }}) · {{ $attempt->package->category->name }}
                · mulai {{ $attempt->started_at->setTimezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }}
                @if ($attempt->submitted_at) · selesai {{ $attempt->submitted_at->setTimezone('Asia/Jakarta')->translatedFormat('H:i') }} @endif
            </p>
        </div>
        @if (auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.users.show', $user) }}" wire:navigate class="btn btn-outline btn-sm">Profil peserta</a>
        @endif
    </div>

    @if ($attempt->isInProgress())
        <div class="rounded-2xl border border-sun-400/50 bg-sun-100 px-5 py-4 text-sm font-semibold text-sun-600">Ujian ini masih berjalan · sisa {{ ceil($attempt->remainingSeconds() / 60) }} menit. Nilai muncul setelah peserta menyelesaikannya.</div>
    @else
        <section class="grid grid-cols-2 gap-4 lg:grid-cols-5">
            @foreach ([
                ['Skor', $format($attempt->score).' / '.$format($attempt->max_score), 'chart'],
                ['Persentase', $attempt->percentage().'%', 'target'],
                ['Benar · salah · kosong', $attempt->correct_count.' · '.$attempt->wrong_count.' · '.$attempt->blank_count, 'check'],
                ['Durasi', $attempt->durationMinutes().' / '.$attempt->package->duration_minutes.' mnt', 'clock'],
                ['Pindah tab', $attempt->tab_switches.'×', 'flag'],
            ] as $index => [$label, $value, $icon])
                <article class="stat-card rise" style="--i: {{ $index + 1 }}">
                    <div class="flex items-center justify-between gap-2">
                        <h2 class="text-xs font-bold text-subtle">{{ $label }}</h2>
                        <x-icon :name="$icon" class="size-4 text-subtle" />
                    </div>
                    <p @class(['mt-2 text-xl font-extrabold tracking-tight', 'text-coral-600' => $label === 'Pindah tab' && $attempt->tab_switches >= 3])>{{ $value }}</p>
                </article>
            @endforeach
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="stat-card overflow-hidden p-0">
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <h2 class="font-bold">Skor per bagian</h2>
                    @if ($attempt->passed === true) <span class="badge bg-brand-50 text-brand-700">Lolos</span>
                    @elseif ($attempt->passed === false) <span class="badge bg-coral-100 text-coral-600">Belum lolos</span> @endif
                </div>
                <ul class="divide-y divide-line">
                    @foreach ($attempt->section_scores ?? [] as $section)
                        <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                            <span class="font-semibold">{{ $section['name'] }}</span>
                            <span class="text-right">
                                <strong>{{ $format($section['score']) }}</strong><span class="text-subtle"> / {{ $format($section['max_score']) }}</span>
                                @if ($section['passing_score']) <span class="block text-xs {{ $section['passed'] ? 'text-brand-700' : 'text-coral-600' }}">ambang {{ $section['passing_score'] }} {{ $section['passed'] ? '✓' : '✗' }}</span> @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="stat-card overflow-hidden p-0">
                <h2 class="border-b border-line px-5 py-4 font-bold">Penguasaan per topik</h2>
                <ul class="divide-y divide-line">
                    @foreach ($topics as $topic)
                        <li class="flex items-center gap-4 px-5 py-3 text-sm">
                            <span class="min-w-0 flex-1 truncate font-semibold">{{ $topic['topic'] }}</span>
                            <span class="h-2 w-28 overflow-hidden rounded-full bg-navy-50"><span @class(['fill-bar block h-full rounded-full', 'bg-brand-500' => $topic['percentage'] >= 70, 'bg-sun-400' => $topic['percentage'] >= 50 && $topic['percentage'] < 70, 'bg-coral-500' => $topic['percentage'] < 50]) style="width: {{ $topic['percentage'] }}%"></span></span>
                            <span class="w-24 text-right text-xs text-muted">{{ $topic['correct'] }}/{{ $topic['total'] }} · {{ $topic['percentage'] }}%</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>

        @if ($attempt->analysis?->result)
            <section class="stat-card p-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="font-bold">Analisis yang diterima peserta</h2>
                    <span class="text-xs text-subtle">{{ $attempt->analysis->source === 'ai' ? 'AI · '.$attempt->analysis->model : 'Berbasis aturan' }}</span>
                </div>
                <p class="mt-3 text-sm leading-6">{{ $attempt->analysis->result['summary'] ?? '' }}</p>
                @if (! empty($attempt->analysis->result['weaknesses']))
                    <p class="mt-3 text-xs font-bold text-coral-600">Kelemahan: {{ collect($attempt->analysis->result['weaknesses'])->pluck('topic')->join(', ') }}</p>
                @endif
            </section>
        @endif
    @endif

    <section class="stat-card overflow-hidden p-0">
        <h2 class="border-b border-line px-5 py-4 font-bold">Jawaban per soal</h2>
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead><tr><th class="w-12">No</th><th>Soal</th><th>Jawaban peserta</th><th>Kunci</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($items as $item)
                        @php
                            $question = $item['question'];
                            $answer = $item['answer'];
                            $optionLetter = fn ($id) => $letters[$question->options->search(fn ($option) => $option->id === (int) $id)] ?? '?';
                            $given = match ($question->type) {
                                \App\Enums\QuestionType::ShortAnswer => $answer?->answer_text,
                                \App\Enums\QuestionType::TrueFalse => collect($answer?->statement_answers ?? [])->map(fn ($value) => $value ? 'B' : 'S')->join(','),
                                default => collect($answer?->selected_option_ids ?? [])->map($optionLetter)->join(','),
                            };
                            $key = match ($question->type) {
                                \App\Enums\QuestionType::ShortAnswer => str_replace('|', ' / ', $question->answer_text),
                                \App\Enums\QuestionType::TrueFalse => $question->options->map(fn ($option) => $option->is_correct ? 'B' : 'S')->join(','),
                                \App\Enums\QuestionType::Weighted => 'bobot '.$question->options->pluck('weight')->join(','),
                                default => $question->options->filter->is_correct->keys()->map(fn ($index) => $letters[$index])->join(','),
                            };
                            $status = $answer?->is_correct === true ? 'benar' : ($answer?->is_correct === false ? 'salah' : 'kosong');
                        @endphp
                        <tr wire:key="answer-{{ $question->id }}">
                            <td class="font-mono text-xs text-subtle">{{ $item['number'] }}</td>
                            <td class="max-w-md">
                                <span class="line-clamp-2 text-sm">{{ \Illuminate\Support\Str::limit(strip_tags($question->body), 140) }}</span>
                                <span class="text-xs text-subtle">{{ $question->topic?->name }}</span>
                            </td>
                            <td class="font-mono text-xs">{{ $given !== '' && $given !== null ? $given : '–' }} @if ($answer?->is_flagged) <span class="ml-1 font-sans text-sun-600" title="Ditandai ragu-ragu">★</span> @endif</td>
                            <td class="font-mono text-xs text-brand-700">{{ $key }}</td>
                            <td>
                                @if ($attempt->isInProgress())
                                    <span class="text-xs text-subtle">{{ $answer && ! $answer->isBlank() ? 'Dijawab' : '–' }}</span>
                                @else
                                    <span @class(['badge', 'bg-brand-50 text-brand-700' => $status === 'benar', 'bg-coral-100 text-coral-600' => $status === 'salah', 'bg-navy-50 text-muted' => $status === 'kosong'])>{{ ucfirst($status) }}@if ($answer && $answer->score != 0) · {{ $format($answer->score) }}@endif</span>
                                @endif
                            </td>
                            <td class="text-right"><a href="{{ route('admin.questions.edit', $question) }}" wire:navigate class="text-xs font-semibold text-brand-700">Soal</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @if ($otherAttempts->isNotEmpty())
        <section class="stat-card overflow-hidden p-0">
            <h2 class="border-b border-line px-5 py-4 font-bold">Pengerjaan lain oleh {{ $user->name }}</h2>
            <ul class="divide-y divide-line text-sm">
                @foreach ($otherAttempts as $other)
                    <li class="flex items-center justify-between gap-3 px-5 py-2.5">
                        <a href="{{ route('admin.attempts.show', $other) }}" wire:navigate class="font-semibold hover:text-brand-700">{{ $other->package->title }}</a>
                        <span class="text-xs text-muted">{{ $other->isInProgress() ? 'Berjalan' : $other->percentage().'%' }} · {{ $other->started_at->setTimezone('Asia/Jakarta')->translatedFormat('d M Y') }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
