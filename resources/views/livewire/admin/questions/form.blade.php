@php $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']; @endphp
<div>
    @if ($course)
        <a href="{{ route('admin.courses.show', ['category' => $course, 'tab' => 'soal']) }}" wire:navigate class="text-sm font-semibold text-muted hover:text-ink">← Bank soal {{ $course->name }}</a>
    @else
        <a href="{{ route('admin.courses.index') }}" wire:navigate class="text-sm font-semibold text-muted hover:text-ink">← Semua kursus</a>
    @endif
    <h1 class="mt-3 mb-6 text-2xl font-extrabold tracking-tight">{{ $question ? 'Ubah soal #'.$question->id : 'Soal baru' }}</h1>

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-[1fr_300px]">
        <div class="space-y-6">
            <section class="card space-y-4 p-5">
                <div>
                    <label for="body" class="field-label">Pertanyaan (HTML)</label>
                    <textarea id="body" wire:model.live.debounce.600ms="body" rows="6" class="field font-mono text-xs leading-5" placeholder="<p>Isi soal...</p>"></textarea>
                    @error('body') <p class="field-error">{{ $message }}</p> @enderror
                    @if ($bodyPreview)
                        <div class="prose-materi mt-3 rounded-xl bg-canvas p-4 text-sm">{!! $bodyPreview !!}</div>
                    @endif
                </div>
            </section>

            <section class="card p-5">
                @if ($type === 'short_answer')
                    <label for="answer" class="field-label">Jawaban benar</label>
                    <input id="answer" type="text" wire:model="answer_text" class="field" placeholder="36">
                    <p class="mt-1 text-xs text-subtle">Pisahkan jawaban alternatif dengan tanda | (contoh: 2,5|2.5). Huruf besar/kecil dan spasi diabaikan.</p>
                    @error('answer_text') <p class="field-error">{{ $message }}</p> @enderror
                @else
                    <div class="flex items-center justify-between">
                        <h2 class="font-bold">
                            @switch($type)
                                @case('true_false') Pernyataan (centang jika pernyataan BENAR) @break
                                @case('weighted') Opsi dan bobot nilai (1–5) @break
                                @case('multiple_choice') Opsi (centang semua yang benar) @break
                                @default Opsi (pilih satu yang benar)
                            @endswitch
                        </h2>
                        <button type="button" wire:click="addOption" class="btn btn-outline btn-sm" @disabled(count($options) >= 8)>+ Opsi</button>
                    </div>
                    @error('options') <p class="field-error">{{ $message }}</p> @enderror
                    <div class="mt-4 space-y-2">
                        @foreach ($options as $index => $option)
                            <div wire:key="option-{{ $index }}" class="flex items-start gap-3 rounded-xl border border-line p-3">
                                <span class="mt-2 w-5 text-sm font-bold text-navy-600">{{ $type === 'true_false' ? $loop->iteration.'.' : $letters[$index] }}</span>
                                <textarea wire:model="options.{{ $index }}.body" rows="1" class="field flex-1 py-2" aria-label="Isi opsi {{ $letters[$index] }}"></textarea>
                                @if ($type === 'weighted')
                                    <select wire:model="options.{{ $index }}.weight" class="field w-20 py-2" aria-label="Bobot opsi {{ $letters[$index] }}">
                                        @foreach (range(1, 5) as $weight)
                                            <option value="{{ $weight }}">{{ $weight }}</option>
                                        @endforeach
                                    </select>
                                @elseif ($type === 'single_choice')
                                    <label class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-muted">
                                        <input type="radio" name="correct" wire:click="markCorrect({{ $index }})" @checked($option['is_correct']) class="size-4 border-line text-brand-600"> Benar
                                    </label>
                                @else
                                    <label class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-muted">
                                        <input type="checkbox" wire:model="options.{{ $index }}.is_correct" class="size-4 rounded border-line text-brand-600"> Benar
                                    </label>
                                @endif
                                <button type="button" wire:click="removeOption({{ $index }})" class="mt-2 text-subtle hover:text-coral-600" aria-label="Hapus opsi {{ $letters[$index] }}">✕</button>
                            </div>
                            @error('options.'.$index.'.body') <p class="field-error">{{ $message }}</p> @enderror
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="card p-5">
                <label for="explanation" class="field-label">Pembahasan (HTML)</label>
                <textarea id="explanation" wire:model="explanation" rows="5" class="field font-mono text-xs leading-5"></textarea>
            </section>
        </div>

        <aside class="card h-fit space-y-4 p-5">
            <div>
                <label for="subject" class="field-label">Mata uji</label>
                <select id="subject" wire:model.live="subject_id" class="field">
                    <option value="">Pilih mata uji</option>
                    @foreach ($categories as $category)
                        <optgroup label="{{ $category->name }}">
                            @foreach ($category->subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('subject_id') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="topic" class="field-label">Topik</label>
                <select id="topic" wire:model="topic_id" class="field" @disabled(! $subject_id)>
                    <option value="">Tanpa topik</option>
                    @foreach ($this->topics as $topic)
                        <option value="{{ $topic->id }}">{{ $topic->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-subtle">Topik dipakai untuk analisis penguasaan peserta.</p>
            </div>
            <div>
                <label for="type" class="field-label">Tipe soal</label>
                <select id="type" wire:model.live="type" class="field">
                    @foreach ($types as $item)
                        <option value="{{ $item->value }}">{{ $item->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="difficulty" class="field-label">Kesulitan</label>
                    <select id="difficulty" wire:model="difficulty" class="field">
                        <option value="1">Mudah</option><option value="2">Sedang</option><option value="3">Sulit</option>
                    </select>
                </div>
                <div>
                    <label for="status" class="field-label">Status</label>
                    <select id="status" wire:model="status" class="field">
                        @foreach ($statuses as $item)
                            <option value="{{ $item->value }}">{{ $item->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label for="source" class="field-label">Sumber soal</label>
                <input id="source" type="text" wire:model="source" class="field" placeholder="Soal orisinal / nama penyusun">
                <p class="mt-1 text-xs text-subtle">Catat sumber untuk mencegah masalah hak cipta.</p>
            </div>
            <button type="submit" class="btn btn-primary w-full">Simpan soal</button>
        </aside>
    </form>
</div>
