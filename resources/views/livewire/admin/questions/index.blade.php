@php
    use App\Support\Html;
    use Illuminate\Support\Js;
    use Illuminate\Support\Str;
@endphp
<div x-data="{ activeQuestion: null }" @keydown.escape.window="activeQuestion = null">
    <x-page-header title="Bank soal" description="Soal melewati alur draf → review → terbit. Hanya soal terbit yang ikut ke ujian.">
        <x-slot:actions>
            <a href="{{ route('admin.questions.import') }}" wire:navigate class="btn btn-outline">Import Excel</a>
            <a href="{{ route('admin.questions.create') }}" wire:navigate class="btn btn-primary">+ Soal baru</a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <select wire:model.live="category" class="field text-sm" aria-label="Filter kategori">
            <option value="">Semua kategori</option>
            @foreach ($categories as $item)
                <option value="{{ $item->id }}">{{ $item->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="status" class="field text-sm" aria-label="Filter status">
            <option value="">Semua status</option>
            @foreach ($statuses as $item)
                <option value="{{ $item->value }}">{{ $item->label() }}</option>
            @endforeach
        </select>
        <select wire:model.live="type" class="field text-sm" aria-label="Filter tipe soal">
            <option value="">Semua tipe</option>
            @foreach ($types as $item)
                <option value="{{ $item->value }}">{{ $item->label() }}</option>
            @endforeach
        </select>
        <input type="search" wire:model.live.debounce.400ms="search" class="field text-sm" placeholder="Cari isi soal" aria-label="Cari soal">
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base text-[15px] [&_td]:py-4 [&_th]:py-3.5 [&_th]:text-[13px]">
                <thead>
                    <tr>
                        <th class="w-5/12">Soal</th>
                        <th>Mata uji / topik</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($questions as $question)
                        @php
                            $questionPayload = [
                                'id' => $question->id,
                                'location' => $question->subject->category->name.' › '.$question->subject->name.($question->topic ? ' › '.$question->topic->name : ''),
                                'type' => $question->type->label(),
                                'difficulty' => $question->difficulty,
                                'sectionsCount' => $question->sections_count,
                                'bodyHtml' => Html::clean($question->body),
                                'explanationHtml' => $question->explanation ? Html::clean($question->explanation) : null,
                                'editUrl' => route('admin.questions.edit', $question),
                                'options' => $question->options->values()->map(fn ($opt, $idx) => [
                                    'label' => chr(65 + $idx),
                                    'body' => strip_tags($opt->body),
                                    'isCorrect' => (bool) $opt->is_correct,
                                    'weight' => $opt->weight,
                                ])->all(),
                            ];
                        @endphp
                        <tr wire:key="question-{{ $question->id }}">
                            <td>
                                <p class="line-clamp-2 text-[15px] font-semibold text-ink">{{ Str::limit(strip_tags($question->body), 140) }}</p>
                                <p class="mt-1 text-sm text-muted">#{{ $question->id }} · kesulitan {{ $question->difficulty }} · dipakai di {{ $question->sections_count }} paket</p>
                            </td>
                            <td>
                                <span class="text-sm font-medium text-ink">{{ $question->subject->category->name }} › {{ $question->subject->name }}</span>
                                @if ($question->topic)
                                    <span class="mt-0.5 block text-sm text-muted">{{ $question->topic->name }}</span>
                                @endif
                            </td>
                            <td class="text-sm font-medium whitespace-nowrap text-ink">{{ $question->type->label() }}</td>
                            <td>
                                <select wire:change="setStatus({{ $question->id }}, $event.target.value)" class="rounded-lg border border-line px-2.5 py-1.5 text-sm font-semibold {{ $question->status->badgeClass() }}" aria-label="Status soal {{ $question->id }}">
                                    @foreach ($statuses as $item)
                                        <option value="{{ $item->value }}" @selected($question->status === $item)>{{ $item->label() }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <button
                                    type="button"
                                    x-on:click="activeQuestion = {{ Js::from($questionPayload) }}"
                                    class="btn btn-outline btn-sm text-sm"
                                >
                                    Pratinjau
                                </button>
                                <a href="{{ route('admin.questions.edit', $question) }}" wire:navigate class="btn btn-ghost btn-sm text-sm">Ubah</a>
                                <button type="button" wire:click="delete({{ $question->id }})" wire:confirm="Hapus soal #{{ $question->id }}?" class="btn btn-ghost btn-sm text-sm text-coral-600">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-base text-muted">Tidak ada soal.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $questions->links() }}</div>

    {{-- Modal pratinjau soal --}}
    <template x-teleport="body">
        <div
            x-cloak
            x-show="activeQuestion !== null"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4 backdrop-blur-xs"
            x-on:click.self="activeQuestion = null"
            role="dialog"
            aria-modal="true"
            aria-labelledby="question-modal-title"
        >
            <div x-show="activeQuestion !== null" x-transition class="flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-line bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-line px-5 py-4">
                    <div>
                        <h2 id="question-modal-title" class="text-lg font-extrabold text-ink" x-text="'Soal #' + (activeQuestion?.id ?? '') + ' · ' + (activeQuestion?.type ?? '')"></h2>
                        <p class="mt-0.5 text-sm text-muted" x-text="activeQuestion?.location"></p>
                    </div>
                    <button type="button" x-on:click="activeQuestion = null" class="grid size-8 flex-none place-items-center rounded-lg text-lg text-subtle hover:bg-navy-50 hover:text-ink" aria-label="Tutup popup">×</button>
                </div>

                <div class="space-y-4 overflow-y-auto px-5 py-4">
                    <div class="prose-materi text-[15px]" x-html="activeQuestion?.bodyHtml"></div>

                    <div x-show="activeQuestion && activeQuestion.options.length > 0" class="space-y-2">
                        <p class="text-xs font-bold tracking-wide text-subtle uppercase">Pilihan jawaban</p>
                        <template x-for="(opt, idx) in (activeQuestion?.options ?? [])" :key="idx">
                            <div
                                class="flex items-start justify-between gap-3 rounded-xl border px-3.5 py-2.5 text-sm"
                                :class="opt.isCorrect ? 'border-brand-500 bg-brand-50/70 font-semibold text-ink' : 'border-line text-muted'"
                            >
                                <span><strong class="mr-1.5 text-ink" x-text="opt.label + '.'"></strong><span x-text="opt.body"></span></span>
                                <span class="whitespace-nowrap text-xs font-bold" :class="opt.isCorrect ? 'text-brand-700' : 'text-subtle'" x-text="opt.isCorrect ? 'Benar' : (opt.weight ? 'Bobot ' + opt.weight : '')"></span>
                            </div>
                        </template>
                    </div>

                    <div x-show="activeQuestion?.explanationHtml" class="rounded-xl bg-canvas p-4">
                        <p class="mb-1 text-xs font-bold tracking-wide text-subtle uppercase">Pembahasan</p>
                        <div class="prose-materi text-sm" x-html="activeQuestion?.explanationHtml"></div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-line bg-canvas/60 px-5 py-3.5">
                    <a :href="activeQuestion?.editUrl" wire:navigate x-on:click="activeQuestion = null" class="btn btn-outline btn-sm text-sm">Ubah soal</a>
                    <button type="button" x-on:click="activeQuestion = null" class="btn btn-primary btn-sm text-sm">Tutup</button>
                </div>
            </div>
        </div>
    </template>
</div>
