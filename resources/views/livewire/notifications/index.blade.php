<div class="mx-auto max-w-2xl">
    <x-page-header title="Notifikasi">
        <x-slot:actions>
            <button type="button" wire:click="markAllRead" class="btn btn-ghost btn-sm">Tandai semua sudah dibaca</button>
        </x-slot:actions>
    </x-page-header>

    <ul class="card divide-y divide-line overflow-hidden">
        @forelse ($notifications as $notification)
            <li wire:key="notification-{{ $notification->id }}">
                <button type="button" wire:click="open('{{ $notification->id }}')" @class(['flex w-full gap-3 px-5 py-4 text-left hover:bg-navy-50/50', 'bg-brand-50/60' => $notification->unread()])>
                    <span @class(['mt-1.5 size-2 flex-none rounded-full', 'bg-brand-500' => $notification->unread(), 'bg-transparent' => ! $notification->unread()]) aria-hidden="true"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold">{{ $notification->data['title'] ?? 'Notifikasi' }}</span>
                        @if (! empty($notification->data['excerpt']))
                            <span class="mt-0.5 block truncate text-sm text-muted">{{ $notification->data['excerpt'] }}</span>
                        @endif
                        <span class="mt-1 block text-xs text-subtle">{{ $notification->created_at->diffForHumans() }}</span>
                    </span>
                </button>
            </li>
        @empty
            <li class="px-5 py-12 text-center text-sm text-muted">Belum ada notifikasi.</li>
        @endforelse
    </ul>
    <div class="mt-4">{{ $notifications->links() }}</div>
</div>
