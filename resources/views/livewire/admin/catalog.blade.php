<div>
    <x-page-header title="Kategori & mata uji" description="Struktur konten: kategori → mata uji → bab. Topik dipakai untuk analisis penguasaan dan dihubungkan ke bab materi." />

    <div class="grid gap-6 lg:grid-cols-[260px_1fr]">
        <aside class="card h-fit p-2">
            @foreach ($this->categories as $category)
                <button type="button" wire:key="cat-{{ $category->id }}" wire:click="selectCategory({{ $category->id }})"
                        @class(['flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-semibold', 'bg-brand-50 text-brand-700' => $selectedCategoryId === $category->id, 'hover:bg-navy-50' => $selectedCategoryId !== $category->id])>
                    <span class="size-2.5 rounded-full" style="background: {{ $category->color }}"></span>
                    <span class="flex-1">{{ $category->name }}</span>
                    @unless ($category->is_active) <span class="text-[10px] text-subtle">nonaktif</span> @endunless
                </button>
            @endforeach
            <button type="button" wire:click="newCategory" class="btn btn-ghost btn-sm mt-1 w-full">+ Kategori baru</button>
        </aside>

        <div class="space-y-6">
            <form wire:submit="saveCategory" class="card grid gap-4 p-5 sm:grid-cols-2">
                <h2 class="font-bold sm:col-span-2">{{ $categoryForm['id'] ? 'Edit kategori' : 'Kategori baru' }}</h2>
                <div>
                    <label class="field-label" for="cat-name">Nama</label>
                    <input id="cat-name" type="text" wire:model="categoryForm.name" class="field">
                    @error('categoryForm.name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="field-label" for="cat-color">Warna</label>
                        <input id="cat-color" type="color" wire:model="categoryForm.color" class="h-10 w-full cursor-pointer rounded-xl border border-line">
                    </div>
                    <div>
                        <label class="field-label" for="cat-order">Urutan</label>
                        <input id="cat-order" type="number" min="0" wire:model="categoryForm.sort_order" class="field">
                    </div>
                </div>
                <div class="sm:col-span-2">
                    <label class="field-label" for="cat-desc">Deskripsi</label>
                    <input id="cat-desc" type="text" wire:model="categoryForm.description" class="field">
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="categoryForm.is_active" class="size-4 rounded border-line text-brand-600"> Tampilkan ke pengguna</label>
                <div class="sm:text-right"><button type="submit" class="btn btn-primary">Simpan kategori</button></div>
            </form>

            @if ($this->selectedCategory)
                <section class="card p-5">
                    <h2 class="font-bold">Mata uji · {{ $this->selectedCategory->name }}</h2>
                    <form wire:submit="addSubject" class="mt-3 flex gap-2">
                        <input type="text" wire:model="newSubject" class="field" placeholder="Nama mata uji baru, misalnya Tes Intelegensia Umum">
                        <button type="submit" class="btn btn-navy whitespace-nowrap">Tambah</button>
                    </form>
                    @error('newSubject') <p class="field-error">{{ $message }}</p> @enderror

                    <div class="mt-5 space-y-4">
                        @foreach ($this->selectedCategory->subjects as $subject)
                            <div wire:key="subject-{{ $subject->id }}" class="rounded-xl border border-line" x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }">
                                <div class="flex items-center gap-2 px-4 py-3">
                                    <button type="button" @click="open = ! open" class="text-subtle" :aria-expanded="open" aria-label="Buka mata uji">
                                        <svg class="size-4 transition" :class="open && 'rotate-90'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                                    </button>
                                    <input type="text" value="{{ $subject->name }}" wire:change="renameSubject({{ $subject->id }}, $event.target.value)" class="flex-1 rounded-lg border border-transparent bg-transparent px-2 py-1 font-semibold hover:border-line focus:border-brand-500 focus:outline-none" aria-label="Nama mata uji">
                                    <span class="text-xs text-subtle">{{ $subject->chapters->count() }} bab · {{ $subject->topics->count() }} topik</span>
                                    <button type="button" wire:click="deleteSubject({{ $subject->id }})" wire:confirm="Hapus mata uji {{ $subject->name }}?" class="btn btn-ghost btn-sm text-coral-600">Hapus</button>
                                </div>

                                <div x-show="open" x-cloak class="grid gap-5 border-t border-line p-4 md:grid-cols-2">
                                    <div>
                                        <h3 class="text-xs font-bold tracking-wide text-subtle uppercase">Bab</h3>
                                        <ul class="mt-2 space-y-1">
                                            @foreach ($subject->chapters as $chapter)
                                                <li wire:key="chapter-{{ $chapter->id }}" class="flex items-center gap-2">
                                                    <input type="text" value="{{ $chapter->title }}" wire:change="renameChapter({{ $chapter->id }}, $event.target.value)" class="flex-1 rounded-lg border border-transparent px-2 py-1 text-sm hover:border-line focus:border-brand-500 focus:outline-none" aria-label="Judul bab">
                                                    <button type="button" wire:click="deleteChapter({{ $chapter->id }})" wire:confirm="Hapus bab ini?" class="text-xs text-coral-600" aria-label="Hapus bab {{ $chapter->title }}">✕</button>
                                                </li>
                                            @endforeach
                                        </ul>
                                        <div class="mt-2 flex gap-2">
                                            <input type="text" wire:model="newChapter.{{ $subject->id }}" wire:keydown.enter.prevent="addChapter({{ $subject->id }})" class="field py-1.5" placeholder="Bab baru">
                                            <button type="button" wire:click="addChapter({{ $subject->id }})" class="btn btn-outline btn-sm">Tambah</button>
                                        </div>
                                    </div>
                                    <div>
                                        <h3 class="text-xs font-bold tracking-wide text-subtle uppercase">Topik</h3>
                                        <ul class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach ($subject->topics as $topic)
                                                <li wire:key="topic-{{ $topic->id }}" class="inline-flex items-center gap-1.5 rounded-full bg-navy-50 px-3 py-1 text-xs font-semibold" title="{{ $topic->chapter ? 'Materi: '.$topic->chapter->title : 'Belum terhubung ke bab' }}">
                                                    {{ $topic->name }} @unless ($topic->chapter) <span class="text-sun-600">•</span> @endunless
                                                    <button type="button" wire:click="deleteTopic({{ $topic->id }})" wire:confirm="Hapus topik {{ $topic->name }}?" class="text-subtle hover:text-coral-600" aria-label="Hapus topik {{ $topic->name }}">✕</button>
                                                </li>
                                            @endforeach
                                        </ul>
                                        <div class="mt-2 grid gap-2 sm:grid-cols-[1fr_auto_auto]">
                                            <input type="text" wire:model="newTopic.{{ $subject->id }}" class="field py-1.5" placeholder="Topik baru">
                                            <select wire:model="newTopicChapter.{{ $subject->id }}" class="field py-1.5" aria-label="Bab materi untuk topik">
                                                <option value="">Tanpa bab</option>
                                                @foreach ($subject->chapters as $chapter)
                                                    <option value="{{ $chapter->id }}">{{ $chapter->title }}</option>
                                                @endforeach
                                            </select>
                                            <button type="button" wire:click="addTopic({{ $subject->id }})" class="btn btn-outline btn-sm">Tambah</button>
                                        </div>
                                        <p class="mt-1 text-xs text-subtle">Topik yang terhubung ke bab akan muncul sebagai tautan materi di rekomendasi hasil try out.</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</div>
