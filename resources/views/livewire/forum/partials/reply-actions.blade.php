@php $voted = $myVotes->contains('reply:'.$item->id); @endphp
<div class="mt-2 flex flex-wrap items-center gap-1">
    <button type="button" wire:click="vote('reply', {{ $item->id }})" @disabled($item->user_id === auth()->id()) aria-pressed="{{ $voted ? 'true' : 'false' }}"
            @class(['btn btn-sm', 'btn-primary' => $voted, 'btn-ghost' => ! $voted])>▲ {{ $item->votes_count }}</button>
    @unless ($thread->is_locked)
        <button type="button" wire:click="$set('replyingTo', {{ $item->id }})" class="btn btn-ghost btn-sm">Balas</button>
    @endunless
    @if ($canBest && $item->user_id !== $thread->user_id)
        <button type="button" wire:click="markBest({{ $item->id }})" class="btn btn-ghost btn-sm text-brand-700">{{ $isBest ? 'Batalkan jawaban terbaik' : '✓ Jadikan jawaban terbaik' }}</button>
    @endif
    <button type="button" wire:click="startReport('reply', {{ $item->id }})" class="btn btn-ghost btn-sm text-subtle">Laporkan</button>
</div>
