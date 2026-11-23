<div>
    <x-page-header title="Pengguna" />

    <div class="mb-4 flex flex-wrap gap-3">
        <input type="search" wire:model.live.debounce.400ms="search" class="field max-w-xs" placeholder="Nama atau email" aria-label="Cari pengguna">
        <select wire:model.live="role" class="field w-auto" aria-label="Filter peran">
            <option value="">Semua peran</option>
            @foreach ($roles as $item) <option value="{{ $item->value }}">{{ $item->label() }}</option> @endforeach
        </select>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead><tr><th>Nama</th><th>Tujuan</th><th>Langganan</th><th>Try out</th><th>Bergabung</th><th></th></tr></thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td>
                                <span class="font-semibold">{{ $user->name }}</span>
                                @if ($user->isAdmin()) <span class="badge ml-1 bg-navy-600 text-white">{{ $user->role->label() }}</span> @endif
                                @if ($user->isBlocked()) <span class="badge ml-1 bg-coral-100 text-coral-600">Diblokir</span> @endif
                                <br><span class="text-xs text-subtle">{{ $user->email }}</span>
                            </td>
                            <td class="text-muted">{{ $user->targetCategory?->name ?? '—' }}</td>
                            <td>{{ $user->subscriptions_count ? 'Aktif' : '—' }}</td>
                            <td>{{ $user->exam_attempts_count }}</td>
                            <td class="text-xs text-muted">{{ $user->created_at->translatedFormat('d M Y') }}</td>
                            <td class="text-right"><a href="{{ route('admin.users.show', $user) }}" wire:navigate class="btn btn-ghost btn-sm">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-muted">Tidak ada pengguna.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
</div>
