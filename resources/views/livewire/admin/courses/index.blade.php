<div @keydown.escape.window="$wire.set('showCreateModal', false)">
    <x-page-header title="Manajemen Kursus" description="Kelola modul, materi pembelajaran, bank soal, dan paket Tes Try Out yang terorganisasi per kursus.">
        <x-slot:actions>
            <button type="button" wire:click="openCreate" class="btn btn-primary">+ Kursus baru</button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($courses as $course)
            @php $materialsTotal = $materialCounts[$course->id] ?? 0; @endphp
            <article wire:key="course-{{ $course->id }}" class="card group relative flex flex-col overflow-hidden p-6 transition hover:border-brand-500">
                <span class="absolute inset-x-0 top-0 h-1.5" style="background: {{ $course->color }}"></span>

                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="truncate text-lg font-extrabold text-ink group-hover:text-brand-700">{{ $course->name }}</h2>
                        <p class="mt-1 line-clamp-2 text-sm text-muted">{{ $course->description ?: 'Belum ada deskripsi kursus.' }}</p>
                    </div>
                    <span class="badge flex-none px-2.5 py-1 text-xs {{ $course->is_active ? 'bg-brand-50 text-brand-700' : 'bg-navy-50 text-muted' }}">
                        {{ $course->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>

                <dl class="mt-5 grid grid-cols-2 gap-2.5 rounded-xl bg-canvas/70 p-3.5 text-sm sm:grid-cols-4">
                    <div>
                        <dt class="text-xs text-subtle">Modul</dt>
                        <dd class="font-extrabold text-ink">{{ $course->subjects_count }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-subtle">Materi</dt>
                        <dd class="font-extrabold text-ink">{{ $materialsTotal }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-subtle">Soal</dt>
                        <dd class="font-extrabold text-ink">{{ $course->questions_count }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-subtle">Try Out</dt>
                        <dd class="font-extrabold text-ink">{{ $course->exam_packages_count }}</dd>
                    </div>
                </dl>

                <div class="mt-5 flex items-center justify-between border-t border-line pt-4">
                    <span class="text-xs font-medium text-subtle">Urutan #{{ $course->sort_order }}</span>
                    <a href="{{ route('admin.courses.show', $course) }}" wire:navigate class="btn btn-primary btn-sm text-sm">
                        Kelola kursus →
                    </a>
                </div>
            </article>
        @empty
            <div class="card col-span-full p-10 text-center text-base text-muted">
                Belum ada kursus. Klik tombol <strong>+ Kursus baru</strong> untuk memulai.
            </div>
        @endforelse
    </div>

    {{-- Modal tambah kursus --}}
    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4 backdrop-blur-xs" wire:click.self="$set('showCreateModal', false)">
            <form wire:submit="createCourse" class="w-full max-w-md space-y-4 rounded-2xl border border-line bg-white p-6 shadow-2xl">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-extrabold text-ink">Kursus baru</h2>
                    <button type="button" wire:click="$set('showCreateModal', false)" class="grid size-8 place-items-center rounded-lg text-lg text-subtle hover:bg-navy-50">×</button>
                </div>

                <div>
                    <label for="new-course-name" class="field-label">Nama kursus</label>
                    <input id="new-course-name" type="text" wire:model="form.name" class="field" placeholder="Contoh: Persiapan UTBK-SNBT">
                    @error('form.name') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="new-course-desc" class="field-label">Deskripsi singkat</label>
                    <input id="new-course-desc" type="text" wire:model="form.description" class="field" placeholder="Penjelasan singkat isi kursus">
                    @error('form.description') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="new-course-color" class="field-label">Warna aksen</label>
                        <input id="new-course-color" type="color" wire:model="form.color" class="h-10 w-full cursor-pointer rounded-xl border border-line">
                    </div>
                    <div>
                        <label for="new-course-order" class="field-label">Urutan tampil</label>
                        <input id="new-course-order" type="number" min="0" wire:model="form.sort_order" class="field">
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm font-medium">
                    <input type="checkbox" wire:model="form.is_active" class="size-4 rounded border-line text-brand-600">
                    Tampilkan ke pengguna
                </label>

                <div class="flex justify-end gap-2 border-t border-line pt-4">
                    <button type="button" wire:click="$set('showCreateModal', false)" class="btn btn-ghost">Batal</button>
                    <button type="submit" class="btn btn-primary">Buat kursus</button>
                </div>
            </form>
        </div>
    @endif
</div>
