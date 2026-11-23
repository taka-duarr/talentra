<section class="card mt-8 p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-bold">Diskusi materi</h2>
        @unless ($showForm)
            <button type="button" wire:click="$set('showForm', true)" class="btn btn-outline btn-sm">+ Tanya tentang materi ini</button>
        @endunless
    </div>

    @if ($showForm)
        <form wire:submit="ask" class="mt-4 space-y-3">
            <input type="text" wire:model="title" maxlength="150" class="field" placeholder="Judul pertanyaan" aria-label="Judul pertanyaan">
            @error('title') <p class="field-error">{{ $message }}</p> @enderror
            <textarea wire:model="body" rows="4" maxlength="5000" class="field" placeholder="Bagian mana yang belum kamu pahami?" aria-label="Isi pertanyaan"></textarea>
            @error('body') <p class="field-error">{{ $message }}</p> @enderror
            @if ($needsGuidelines) <x-forum.guidelines /> @endif
            <div class="flex justify-end gap-2">
                <button type="button" wire:click="$set('showForm', false)" class="btn btn-ghost">Batal</button>
                <button type="submit" class="btn btn-primary">Kirim pertanyaan</button>
            </div>
        </form>
    @endif

    <ul class="mt-4 divide-y divide-line">
        @forelse ($threads as $thread)
            <li wire:key="material-thread-{{ $thread->id }}">
                <a href="{{ route('forum.thread', $thread) }}" wire:navigate class="flex items-center gap-3 py-3 text-sm hover:text-brand-700">
                    <span class="flex-1 font-semibold">{{ $thread->title }}</span>
                    @if ($thread->best_reply_id) <span class="badge bg-brand-50 text-brand-700">✓ Terjawab</span> @endif
                    <span class="text-xs text-subtle">{{ $thread->replies_count }} balasan</span>
                </a>
            </li>
        @empty
            <li class="py-4 text-sm text-muted">Belum ada pertanyaan. Ada yang membingungkan? Tanyakan di sini.</li>
        @endforelse
    </ul>
</section>
