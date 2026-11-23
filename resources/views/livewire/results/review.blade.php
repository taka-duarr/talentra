@php
    $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
    $filters = ['semua' => 'Semua', 'salah' => 'Salah', 'benar' => 'Benar', 'kosong' => 'Kosong', 'ragu' => 'Ragu-ragu'];
@endphp
<div class="mx-auto max-w-3xl">
    <a href="{{ route('results.show', $attempt) }}" wire:navigate class="text-sm font-semibold text-muted hover:text-ink">← Kembali ke hasil</a>
    <h1 class="mt-3 text-2xl font-extrabold tracking-tight">Pembahasan · {{ $attempt->package->title }}</h1>

    <div class="mt-5 flex flex-wrap gap-2" role="group" aria-label="Filter soal">
        @foreach ($filters as $key => $label)
            <button type="button" wire:key="filter-{{ $key }}" wire:click="$set('filter', '{{ $key }}')" aria-pressed="{{ $filter === $key ? 'true' : 'false' }}"
                    @class(['btn btn-sm', 'btn-navy' => $filter === $key, 'btn-outline' => $filter !== $key])>{{ $label }}</button>
        @endforeach
    </div>

    <div class="mt-6 space-y-5">
        @forelse ($items as $item)
            @php
                $question = $item['question'];
                $answer = $item['answer'];
                $selected = collect($answer?->selected_option_ids ?? []);
                $statements = collect($answer?->statement_answers ?? []);
                $status = $answer?->is_correct === true ? 'benar' : ($answer?->is_correct === false ? 'salah' : 'kosong');
                $showExplanation = ! $limitExplanations || $item['number'] <= \App\Livewire\Results\ResultReview::DEMO_EXPLANATIONS;
            @endphp
            <article wire:key="review-{{ $question->id }}" class="card p-5 sm:p-6">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="grid size-8 place-items-center rounded-lg bg-navy-600 text-sm font-bold text-white">{{ $item['number'] }}</span>
                    @if ($question->topic)
                        <span class="text-xs font-semibold text-subtle">{{ $question->topic->name }}</span>
                    @endif
                    <span @class([
                        'badge ml-auto',
                        'bg-brand-50 text-brand-700' => $status === 'benar',
                        'bg-coral-100 text-coral-600' => $status === 'salah',
                        'bg-navy-50 text-muted' => $status === 'kosong',
                    ])>
                        {{ ucfirst($status) }} @if ($answer && $answer->score != 0) · {{ $answer->score > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($answer->score, 2, ',', ''), '0'), ',') }} @endif
                    </span>
                </div>

                <div class="prose-materi mt-4">{!! $question->body !!}</div>

                @if ($question->type === \App\Enums\QuestionType::ShortAnswer)
                    <dl class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
                        <div class="rounded-xl bg-canvas p-3"><dt class="text-xs text-subtle">Jawabanmu</dt><dd class="font-semibold">{{ $answer?->answer_text ?: '—' }}</dd></div>
                        <div class="rounded-xl bg-brand-50 p-3"><dt class="text-xs text-brand-700">Jawaban benar</dt><dd class="font-semibold text-brand-700">{{ str_replace('|', ' / ', $question->answer_text) }}</dd></div>
                    </dl>
                @elseif ($question->type === \App\Enums\QuestionType::TrueFalse)
                    <table class="mt-4 w-full text-sm">
                        <thead class="text-xs text-subtle"><tr><th class="py-1.5 text-left">Pernyataan</th><th class="w-24">Jawabanmu</th><th class="w-24">Kunci</th></tr></thead>
                        <tbody>
                            @foreach ($question->options as $option)
                                @php $given = $statements->get((string) $option->id); @endphp
                                <tr class="border-t border-line">
                                    <td class="py-2 pr-2">{!! $option->body !!}</td>
                                    <td @class(['text-center font-semibold', 'text-brand-700' => $given === $option->is_correct, 'text-coral-600' => $given !== null && $given !== $option->is_correct])>{{ $given === null ? '—' : ($given ? 'Benar' : 'Salah') }}</td>
                                    <td class="text-center font-semibold text-brand-700">{{ $option->is_correct ? 'Benar' : 'Salah' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <ul class="mt-4 space-y-2">
                        @foreach ($question->options as $index => $option)
                            @php
                                $isSelected = $selected->contains($option->id);
                                $isKey = $question->type === \App\Enums\QuestionType::Weighted ? $option->weight === $question->options->max('weight') : $option->is_correct;
                            @endphp
                            <li @class([
                                'flex items-start gap-3 rounded-xl border px-4 py-2.5 text-sm',
                                'border-brand-500 bg-brand-50' => $isKey,
                                'border-coral-500 bg-coral-100/50' => $isSelected && ! $isKey,
                                'border-line' => ! $isKey && ! $isSelected,
                            ])>
                                <span class="font-bold text-navy-600">{{ $letters[$index] ?? '' }}.</span>
                                <span class="flex-1">{!! $option->body !!}</span>
                                @if ($question->type === \App\Enums\QuestionType::Weighted)
                                    <span class="text-xs font-bold text-subtle">nilai {{ $option->weight }}</span>
                                @endif
                                @if ($isSelected)
                                    <span class="text-xs font-bold {{ $isKey ? 'text-brand-700' : 'text-coral-600' }}">Pilihanmu</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($question->explanation)
                    <div class="mt-4 rounded-xl border-l-4 border-sun-400 bg-sun-100/50 px-4 py-3">
                        <p class="text-xs font-bold text-sun-600">Pembahasan</p>
                        @if ($showExplanation)
                            <div class="prose-materi mt-1 text-sm">{!! $question->explanation !!}</div>
                        @else
                            <p class="mt-1 text-sm text-muted">Pembahasan lengkap tersedia untuk pelanggan. <a href="{{ route('plans.index') }}" wire:navigate class="font-semibold text-brand-700 hover:underline">Lihat paket</a></p>
                        @endif
                    </div>
                @endif
            </article>
        @empty
            <p class="card p-8 text-center text-muted">Tidak ada soal untuk filter ini.</p>
        @endforelse
    </div>
</div>
