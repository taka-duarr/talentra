<a href="{{ route('notifications.index') }}" wire:navigate wire:poll.60s class="relative grid size-9 place-items-center rounded-xl text-muted hover:bg-navy-50 hover:text-ink" aria-label="Notifikasi{{ $unread ? ', '.$unread.' belum dibaca' : '' }}">
    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
    @if ($unread)
        <span class="absolute -top-0.5 -right-0.5 grid min-w-4 place-items-center rounded-full bg-coral-600 px-1 text-[10px] font-bold text-white">{{ $unread > 9 ? '9+' : $unread }}</span>
    @endif
</a>
