<div>
    @if ($course)
        <a href="{{ route('admin.courses.show', ['category' => $course, 'tab' => 'kurikulum']) }}" wire:navigate class="text-sm font-semibold text-muted hover:text-ink">← Kurikulum {{ $course->name }}</a>
    @else
        <a href="{{ route('admin.courses.index') }}" wire:navigate class="text-sm font-semibold text-muted hover:text-ink">← Semua kursus</a>
    @endif
    <h1 class="mt-3 mb-6 text-2xl font-extrabold tracking-tight">{{ $material ? 'Ubah materi' : 'Materi baru' }}</h1>

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-[1fr_300px]">
        <div class="card space-y-5 p-5" x-data="{ tab: 'edit' }">
            <div>
                <label for="title" class="field-label">Judul</label>
                <input id="title" type="text" wire:model.blur="title" class="field">
                @error('title') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="slug" class="field-label">Slug URL</label>
                <input id="slug" type="text" wire:model="slug" class="field font-mono text-xs">
                @error('slug') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <div class="mb-1.5 flex items-center justify-between">
                    <label for="content" class="field-label mb-0">Isi materi (HTML)</label>
                    <div class="flex gap-1 text-xs font-semibold" role="tablist">
                        <button type="button" @click="tab = 'edit'" :class="tab === 'edit' ? 'bg-navy-600 text-white' : 'text-muted'" class="rounded-lg px-2.5 py-1">Tulis</button>
                        <button type="button" @click="tab = 'preview'" :class="tab === 'preview' ? 'bg-navy-600 text-white' : 'text-muted'" class="rounded-lg px-2.5 py-1">Pratinjau</button>
                    </div>
                </div>
                <textarea x-show="tab === 'edit'" id="content" wire:model.live.debounce.600ms="content" rows="18" class="field font-mono text-xs leading-5" placeholder="<p>Paragraf...</p>&#10;<h2>Subjudul</h2>&#10;<ul><li>Poin</li></ul>"></textarea>
                <div x-show="tab === 'preview'" x-cloak class="prose-materi min-h-60 rounded-xl border border-line p-5">{!! $preview !!}</div>
                <p class="mt-1 text-xs text-subtle">Tag yang diizinkan: p, h2, h3, strong, em, ul, ol, li, blockquote, table, img, a, code. Skrip dan atribut berbahaya dibuang otomatis.</p>
                @error('content') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <aside class="card h-fit space-y-5 p-5">
            <div>
                <label for="chapter" class="field-label">Bab</label>
                <select id="chapter" wire:model="chapter_id" class="field">
                    <option value="">Pilih bab</option>
                    @foreach ($categories as $category)
                        @foreach ($category->subjects as $subject)
                            <optgroup label="{{ $category->name }} › {{ $subject->name }}">
                                @foreach ($subject->chapters as $chapter)
                                    <option value="{{ $chapter->id }}">{{ $chapter->title }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    @endforeach
                </select>
                @error('chapter_id') <p class="field-error">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-subtle">Belum ada babnya? Tambahkan di <a href="{{ $course ? route('admin.courses.show', ['category' => $course, 'tab' => 'kurikulum']) : route('admin.courses.index') }}" wire:navigate class="font-semibold text-brand-700">halaman kurikulum kursus</a>.</p>
            </div>
            <div>
                <label for="video" class="field-label">Video (YouTube/Vimeo, opsional)</label>
                <input id="video" type="url" wire:model="video_url" class="field" placeholder="https://youtu.be/...">
                @error('video_url') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="order" class="field-label">Urutan dalam bab</label>
                <input id="order" type="number" min="0" wire:model="sort_order" class="field">
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_free" class="size-4 rounded border-line text-brand-600"> Gratis (bisa dibaca tanpa langganan)</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_published" class="size-4 rounded border-line text-brand-600"> Terbitkan</label>
            <button type="submit" class="btn btn-primary w-full">Simpan materi</button>
        </aside>
    </form>
</div>
