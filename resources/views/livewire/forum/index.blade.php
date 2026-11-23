<div>
    <x-page-header title="Forum diskusi" description="Tanya, berbagi tips, dan bahas soal sulit bersama pejuang ujian lainnya.">
        <x-slot:actions>
            <a href="{{ route('forum.create', ['ruang' => $room]) }}" wire:navigate class="btn btn-primary">+ Mulai diskusi</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-[220px_1fr]">
        <nav class="card h-fit p-2" aria-label="Ruang forum">
            <button type="button" wire:click="$set('room', 'umum')" @class(['w-full rounded-lg px-3 py-2 text-left text-sm font-semibold', 'bg-brand-50 text-brand-700' => $room === 'umum', 'hover:bg-navy-50' => $room !== 'umum'])>💬 Forum umum</button>
            <p class="px-3 pt-3 pb-1 text-[11px] font-bold tracking-wider text-subtle uppercase">Per kategori</p>
            @foreach ($categories as $category)
                <button type="button" wire:key="room-{{ $category->id }}" wire:click="$set('room', '{{ $category->slug }}')"
                        @class(['flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-semibold', 'bg-brand-50 text-brand-700' => $room === $category->slug, 'hover:bg-navy-50' => $room !== $category->slug])>
                    <span class="size-2 rounded-full" style="background: {{ $category->color }}"></span>{{ $category->name }}
                </button>
            @endforeach
        </nav>

        <div>
            <div class="mb-4 flex flex-wrap gap-2">
                <input type="search" wire:model.live.debounce.400ms="search" class="field max-w-xs" placeholder="Cari diskusi" aria-label="Cari diskusi">
                <select wire:model.live="tag" class="field w-auto" aria-label="Filter label">
                    <option value="">Semua label</option>
                    @foreach ($tags as $item) <option value="{{ $item->value }}">{{ $item->label() }}</option> @endforeach
                </select>
                <select wire:model.live="sort" class="field w-auto" aria-label="Urutkan">
                    <option value="terbaru">Aktivitas terbaru</option>
                    <option value="populer">Terpopuler</option>
                    <option value="belum-terjawab">Belum terjawab</option>
                </select>
            </div>

            <ul class="space-y-3">
                @forelse ($threads as $thread)
                    <li wire:key="thread-{{ $thread->id }}">
                        <a href="{{ route('forum.thread', $thread) }}" wire:navigate class="card flex gap-4 p-4 transition hover:border-brand-500">
                            <div class="hidden w-14 flex-none flex-col items-center justify-center rounded-xl bg-canvas py-2 text-center sm:flex">
                                <span class="text-lg font-extrabold">{{ $thread->votes_count }}</span>
                                <span class="text-[10px] text-subtle">suara</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @if ($thread->is_pinned) <span class="badge bg-navy-600 text-white">📌 Disematkan</span> @endif
                                    <span class="badge {{ $thread->tag->badgeClass() }}">{{ $thread->tag->label() }}</span>
                                    @if ($thread->material) <span class="badge bg-navy-50 text-muted">Materi: {{ \Illuminate\Support\Str::limit($thread->material->title, 30) }}</span> @endif
                                    @if ($thread->best_reply_id) <span class="badge bg-brand-50 text-brand-700">✓ Terjawab</span> @endif
                                    @if ($thread->is_locked) <span class="badge bg-navy-50 text-muted">🔒 Dikunci</span> @endif
                                </div>
                                <h2 class="mt-1.5 font-bold">{{ $thread->title }}</h2>
                                <p class="mt-1 line-clamp-1 text-sm text-muted">{{ $thread->body }}</p>
                                <p class="mt-2 text-xs text-subtle">{{ $thread->author->publicName() }} · {{ $thread->replies_count }} balasan · {{ $thread->last_activity_at?->diffForHumans() }}</p>
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="card p-10 text-center text-muted">Belum ada diskusi di sini. Jadilah yang pertama memulai!</li>
                @endforelse
            </ul>
            <div class="mt-4">{{ $threads->links() }}</div>
        </div>
    </div>
</div>
