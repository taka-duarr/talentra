<div>
    <x-page-header title="Materi">
        <x-slot:actions>
            <a href="{{ route('admin.materials.create') }}" wire:navigate class="btn btn-primary">+ Materi baru</a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 flex flex-wrap gap-3">
        <select wire:model.live="category" class="field w-auto" aria-label="Filter kategori">
            <option value="">Semua kategori</option>
            @foreach ($categories as $item)
                <option value="{{ $item->id }}">{{ $item->name }}</option>
            @endforeach
        </select>
        <input type="search" wire:model.live.debounce.400ms="search" class="field max-w-xs" placeholder="Cari judul materi" aria-label="Cari materi">
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead><tr><th>Judul</th><th>Lokasi</th><th>Akses</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($materials as $material)
                        <tr wire:key="material-{{ $material->id }}">
                            <td class="font-semibold">{{ $material->title }}</td>
                            <td class="text-xs text-muted">{{ $material->chapter->subject->category->name }} › {{ $material->chapter->subject->name }} › {{ $material->chapter->title }}</td>
                            <td>@if ($material->is_free) <span class="badge bg-sun-100 text-sun-600">Gratis</span> @else <span class="badge bg-navy-50 text-muted">Langganan</span> @endif</td>
                            <td>@if ($material->published_at) <span class="badge bg-brand-50 text-brand-700">Terbit</span> @else <span class="badge bg-navy-50 text-muted">Draf</span> @endif</td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.materials.edit', $material) }}" wire:navigate class="btn btn-ghost btn-sm">Ubah</a>
                                <button type="button" wire:click="delete({{ $material->id }})" wire:confirm="Hapus materi {{ $material->title }}?" class="btn btn-ghost btn-sm text-coral-600">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-10 text-center text-muted">Belum ada materi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $materials->links() }}</div>
</div>
