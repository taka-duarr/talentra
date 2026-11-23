@php
    $backRoom = $thread->category?->slug ?? 'umum';
    $renderBody = fn (string $text) => nl2br(e($text));
@endphp
<div class="mx-auto max-w-3xl">
    @if ($thread->material)
        <a href="{{ route('learn.material', $thread->material) }}" wire:navigate class="text-sm font-semibold text-muted hover:text-ink">← Materi: {{ $thread->material->title }}</a>
    @else
        <a href="{{ route('forum.index', ['ruang' => $backRoom]) }}" wire:navigate class="text-sm font-semibold text-muted hover:text-ink">← {{ $thread->category?->name ? 'Forum '.$thread->category->name : 'Forum umum' }}</a>
    @endif

    {{-- Thread --}}
    <article class="card mt-4 p-5 sm:p-6">
        <div class="flex flex-wrap items-center gap-1.5">
            @if ($thread->is_pinned) <span class="badge bg-navy-600 text-white">📌 Disematkan</span> @endif
            <span class="badge {{ $thread->tag->badgeClass() }}">{{ $thread->tag->label() }}</span>
            @if ($thread->is_locked) <span class="badge bg-navy-50 text-muted">🔒 Dikunci</span> @endif
        </div>
        <h1 class="mt-2 text-2xl font-extrabold tracking-tight">{{ $thread->title }}</h1>
        <div class="mt-3 flex items-center gap-2 text-xs text-subtle">
            <x-forum.author :user="$thread->author" :badges="$badges[$thread->user_id] ?? []" />
            <span>· {{ $thread->created_at->diffForHumans() }}</span>
        </div>
        <div class="mt-4 text-[15px] leading-7">{!! $renderBody($thread->body) !!}</div>

        <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-line pt-4">
            @php $voted = $myVotes->contains('thread:'.$thread->id); @endphp
            <button type="button" wire:click="vote('thread', {{ $thread->id }})" @disabled($thread->user_id === auth()->id()) aria-pressed="{{ $voted ? 'true' : 'false' }}"
                    @class(['btn btn-sm', 'btn-primary' => $voted, 'btn-outline' => ! $voted])>▲ {{ $thread->votes_count }}</button>
            <button type="button" wire:click="startReport('thread', {{ $thread->id }})" class="btn btn-ghost btn-sm">Laporkan</button>
            @if (auth()->user()->isAdmin())
                <button type="button" wire:click="togglePin" class="btn btn-ghost btn-sm">{{ $thread->is_pinned ? 'Lepas sematan' : 'Sematkan' }}</button>
                <button type="button" wire:click="toggleLock" class="btn btn-ghost btn-sm">{{ $thread->is_locked ? 'Buka kunci' : 'Kunci diskusi' }}</button>
            @endif
        </div>
    </article>

    {{-- Replies --}}
    <h2 class="mt-8 mb-3 font-bold">{{ $thread->replies_count }} balasan</h2>
    <div class="space-y-3">
        @foreach ($replies as $reply)
            @php $isBest = $thread->best_reply_id === $reply->id; @endphp
            <div wire:key="reply-{{ $reply->id }}" id="balasan-{{ $reply->id }}" @class(['card p-4', 'border-brand-500 ring-1 ring-brand-500' => $isBest])>
                @if ($isBest) <p class="mb-2 text-xs font-bold text-brand-700">✓ Jawaban terbaik</p> @endif
                <div class="flex items-center gap-2 text-xs text-subtle">
                    <x-forum.author :user="$reply->author" :badges="$badges[$reply->user_id] ?? []" />
                    <span>· {{ $reply->created_at->diffForHumans() }}</span>
                </div>
                <div class="mt-2 text-sm leading-6">{!! $renderBody($reply->body) !!}</div>
                @include('livewire.forum.partials.reply-actions', ['item' => $reply, 'canBest' => $canMarkBest, 'isBest' => $isBest])

                @if ($reply->children->isNotEmpty())
                    <div class="mt-3 space-y-3 border-l-2 border-line pl-4">
                        @foreach ($reply->children as $child)
                            <div wire:key="reply-{{ $child->id }}" id="balasan-{{ $child->id }}">
                                <div class="flex items-center gap-2 text-xs text-subtle">
                                    <x-forum.author :user="$child->author" :badges="$badges[$child->user_id] ?? []" />
                                    <span>· {{ $child->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="mt-1.5 text-sm leading-6">{!! $renderBody($child->body) !!}</div>
                                @include('livewire.forum.partials.reply-actions', ['item' => $child, 'canBest' => false, 'isBest' => false])
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Reply form --}}
    @if ($thread->is_locked && ! auth()->user()->isAdmin())
        <p class="card mt-6 p-5 text-center text-sm text-muted">🔒 Diskusi ini dikunci. Balasan baru tidak bisa ditambahkan.</p>
    @else
        <form wire:submit="reply" class="card mt-6 space-y-4 p-5">
            <h2 class="font-bold">Tulis balasan</h2>
            @if ($replyingToReply)
                <p class="flex items-center justify-between rounded-lg bg-canvas px-3 py-2 text-xs text-muted">
                    Membalas {{ $replyingToReply->author->publicName() }}
                    <button type="button" wire:click="$set('replyingTo', null)" class="font-semibold text-coral-600">Batal</button>
                </p>
            @endif
            <textarea wire:model="body" rows="4" maxlength="5000" class="field" placeholder="Bagikan jawaban atau pendapatmu dengan sopan." aria-label="Isi balasan"></textarea>
            @error('body') <p class="field-error">{{ $message }}</p> @enderror
            @if ($needsGuidelines) <x-forum.guidelines /> @endif
            <div class="text-right"><button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Kirim balasan</button></div>
        </form>
    @endif

    {{-- Report dialog --}}
    @if ($reporting)
        <div class="fixed inset-0 z-50 grid place-items-center bg-ink/40 p-4" role="dialog" aria-modal="true" aria-labelledby="report-title">
            <form wire:submit="submitReport" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <h2 id="report-title" class="text-lg font-extrabold">Laporkan tulisan</h2>
                <p class="mt-1 text-sm text-muted">Admin akan meninjau laporanmu. Identitasmu tidak ditampilkan ke penulis.</p>
                <textarea wire:model="reportReason" rows="3" class="field mt-4" placeholder="Contoh: berisi spam / kata kasar / membagikan soal rahasia" aria-label="Alasan laporan"></textarea>
                @error('reason') <p class="field-error">{{ $message }}</p> @enderror
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" wire:click="$set('reporting', null)" class="btn btn-outline">Batal</button>
                    <button type="submit" class="btn btn-danger">Kirim laporan</button>
                </div>
            </form>
        </div>
    @endif
</div>
