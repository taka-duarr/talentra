<div>
    <x-page-header title="Paket try out">
        <x-slot:actions>
            <a href="{{ route('admin.packages.create') }}" wire:navigate class="btn btn-primary">+ Paket baru</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead><tr><th>Paket</th><th>Kategori</th><th>Soal</th><th>Durasi</th><th>Dikerjakan</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($packages as $package)
                        <tr wire:key="package-{{ $package->id }}">
                            <td class="font-semibold">{{ $package->title }} @if ($package->is_free) <span class="badge ml-1 bg-sun-100 text-sun-600">Demo</span> @endif</td>
                            <td class="text-muted">{{ $package->category->name }}</td>
                            <td>{{ $package->sections->sum('questions_count') }}</td>
                            <td>{{ $package->duration_minutes }} mnt</td>
                            <td>{{ $package->attempts_count }}×</td>
                            <td><span class="badge {{ $package->status->badgeClass() }}">{{ $package->status->label() }}</span></td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.packages.edit', $package) }}" wire:navigate class="btn btn-ghost btn-sm">Kelola</a>
                                <button type="button" wire:click="delete({{ $package->id }})" wire:confirm="Hapus paket {{ $package->title }}?" class="btn btn-ghost btn-sm text-coral-600">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-muted">Belum ada paket.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
