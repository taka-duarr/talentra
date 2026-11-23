<div>
    <a href="{{ route('admin.packages.index') }}" wire:navigate class="text-sm font-semibold text-muted hover:text-ink">← Paket try out</a>
    <h1 class="mt-3 mb-6 text-2xl font-extrabold tracking-tight">{{ $package ? $package->title : 'Paket baru' }}</h1>

    <form wire:submit="save" class="card grid gap-4 p-5 md:grid-cols-3">
        <div class="md:col-span-2">
            <label for="title" class="field-label">Judul</label>
            <input id="title" type="text" wire:model.blur="form.title" class="field">
            @error('form.title') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="category" class="field-label">Kategori</label>
            <select id="category" wire:model="form.category_id" class="field" @disabled($package)>
                <option value="">Pilih kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            @error('form.category_id') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div class="md:col-span-2">
            <label for="description" class="field-label">Deskripsi</label>
            <input id="description" type="text" wire:model="form.description" class="field">
        </div>
        <div>
            <label for="slug" class="field-label">Slug URL</label>
            <input id="slug" type="text" wire:model="form.slug" class="field font-mono text-xs">
            @error('form.slug') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div><label for="duration" class="field-label">Durasi (menit)</label><input id="duration" type="number" min="1" wire:model="form.duration_minutes" class="field">@error('form.duration_minutes') <p class="field-error">{{ $message }}</p> @enderror</div>
        <div><label for="attempts" class="field-label">Maks. percobaan</label><input id="attempts" type="number" min="1" wire:model="form.max_attempts" class="field" placeholder="Tanpa batas"></div>
        <div><label for="passing" class="field-label">Ambang batas total</label><input id="passing" type="number" min="0" wire:model="form.passing_score" class="field" placeholder="Opsional"></div>

        <fieldset class="grid grid-cols-3 gap-3 md:col-span-2">
            <legend class="field-label">Nilai per soal (soal TKP memakai bobot opsi)</legend>
            <div><label for="correct" class="text-xs text-subtle">Benar</label><input id="correct" type="number" wire:model="form.score_correct" class="field"></div>
            <div><label for="wrong" class="text-xs text-subtle">Salah</label><input id="wrong" type="number" wire:model="form.score_wrong" class="field"></div>
            <div><label for="blank" class="text-xs text-subtle">Kosong</label><input id="blank" type="number" wire:model="form.score_blank" class="field"></div>
        </fieldset>
        <div>
            <label for="status" class="field-label">Status</label>
            <select id="status" wire:model="form.status" class="field">
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm md:col-span-3">
            <label class="flex items-center gap-2"><input type="checkbox" wire:model="form.is_free" class="size-4 rounded border-line text-brand-600"> Demo gratis</label>
            <label class="flex items-center gap-2"><input type="checkbox" wire:model="form.shuffle_questions" class="size-4 rounded border-line text-brand-600"> Acak urutan soal</label>
            <label class="flex items-center gap-2"><input type="checkbox" wire:model="form.shuffle_options" class="size-4 rounded border-line text-brand-600"> Acak urutan opsi</label>
            <label class="flex items-center gap-2"><input type="checkbox" wire:model="form.counts_for_ranking" class="size-4 rounded border-line text-brand-600"> Masuk ranking</label>
        </div>
        <div class="md:col-span-3 md:text-right"><button type="submit" class="btn btn-primary">{{ $package ? 'Simpan pengaturan' : 'Buat paket' }}</button></div>
    </form>

    @if ($package)
        <div class="mt-6 grid gap-6 lg:grid-cols-[320px_1fr]">
            <section class="card h-fit p-5">
                <h2 class="font-bold">Bagian / subtes</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($this->sections as $section)
                        <li wire:key="section-{{ $section->id }}" @class(['rounded-xl border p-3', 'border-brand-500 bg-brand-50' => $activeSectionId === $section->id, 'border-line' => $activeSectionId !== $section->id])>
                            <button type="button" wire:click="selectSection({{ $section->id }})" class="w-full text-left">
                                <span class="block font-semibold">{{ $section->name }}</span>
                                <span class="block text-xs text-subtle">{{ $section->questions->count() }} soal @if ($section->subject) · {{ $section->subject->name }} @endif</span>
                            </button>
                            <div class="mt-2 flex items-center gap-2">
                                <label class="text-xs text-subtle" for="passing-{{ $section->id }}">Ambang</label>
                                <input id="passing-{{ $section->id }}" type="number" min="0" value="{{ $section->passing_score }}" wire:change="updateSectionPassing({{ $section->id }}, $event.target.value)" class="field w-20 py-1 text-xs">
                                <button type="button" wire:click="deleteSection({{ $section->id }})" wire:confirm="Hapus bagian {{ $section->name }}?" class="ml-auto text-xs font-semibold text-coral-600">Hapus</button>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <form wire:submit="addSection" class="mt-4 space-y-2 border-t border-line pt-4">
                    <input type="text" wire:model="newSection.name" class="field" placeholder="Nama bagian, misalnya TWK" aria-label="Nama bagian baru">
                    @error('newSection.name') <p class="field-error">{{ $message }}</p> @enderror
                    <select wire:model="newSection.subject_id" class="field" aria-label="Mata uji bagian baru">
                        <option value="">Semua mata uji di kategori</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                        @endforeach
                    </select>
                    <input type="number" min="0" wire:model="newSection.passing_score" class="field" placeholder="Ambang batas (opsional)" aria-label="Ambang batas bagian baru">
                    <button type="submit" class="btn btn-navy w-full">+ Tambah bagian</button>
                </form>
            </section>

            <section class="card p-5">
                @if ($this->activeSection)
                    <h2 class="font-bold">Soal di bagian {{ $this->activeSection->name }}</h2>
                    <ol class="mt-3 divide-y divide-line rounded-xl border border-line">
                        @forelse ($this->activeSection->questions as $question)
                            <li wire:key="in-{{ $question->id }}" class="flex items-start gap-3 px-4 py-2.5 text-sm">
                                <span class="w-6 font-bold text-subtle">{{ $loop->iteration }}</span>
                                <span class="flex-1">{{ \Illuminate\Support\Str::limit(strip_tags($question->body), 110) }} <span class="text-xs text-subtle">{{ $question->topic?->name }}</span></span>
                                <button type="button" wire:click="detachQuestion({{ $question->id }})" class="text-xs font-semibold text-coral-600">Keluarkan</button>
                            </li>
                        @empty
                            <li class="px-4 py-6 text-center text-sm text-muted">Belum ada soal. Tambahkan dari bank soal di bawah.</li>
                        @endforelse
                    </ol>

                    <h3 class="mt-6 text-sm font-bold">Tambah dari bank soal (hanya soal terbit)</h3>
                    <input type="search" wire:model.live.debounce.400ms="questionSearch" class="field mt-2" placeholder="Cari isi soal" aria-label="Cari soal untuk ditambahkan">
                    <ul class="mt-3 max-h-96 divide-y divide-line overflow-y-auto rounded-xl border border-line">
                        @forelse ($this->availableQuestions as $question)
                            <li wire:key="avail-{{ $question->id }}" class="flex items-start gap-3 px-4 py-2.5 text-sm">
                                <span class="flex-1">{{ \Illuminate\Support\Str::limit(strip_tags($question->body), 110) }} <span class="text-xs text-subtle">#{{ $question->id }} · {{ $question->topic?->name }}</span></span>
                                <button type="button" wire:click="attachQuestion({{ $question->id }})" class="btn btn-outline btn-sm">Tambah</button>
                            </li>
                        @empty
                            <li class="px-4 py-6 text-center text-sm text-muted">Tidak ada soal terbit yang cocok. <a href="{{ route('admin.questions.create') }}" wire:navigate class="font-semibold text-brand-700">Buat soal</a></li>
                        @endforelse
                    </ul>
                @else
                    <p class="py-10 text-center text-sm text-muted">Buat bagian dulu, lalu pilih soalnya.</p>
                @endif
            </section>
        </div>
    @endif
</div>
