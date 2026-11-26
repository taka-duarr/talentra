@php
    use App\Support\Html;
    use Illuminate\Support\Js;
@endphp
<div x-data="{ activeMaterial: null }" @keydown.escape.window="activeMaterial = null">
    <x-page-header title="Materi">
        <x-slot:actions>
            <a href="{{ route('admin.materials.create') }}" wire:navigate class="btn btn-primary">+ Materi baru</a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 flex flex-wrap gap-3">
        <select wire:model.live="category" class="field w-auto text-sm" aria-label="Filter kategori">
            <option value="">Semua kategori</option>
            @foreach ($categories as $item)
                <option value="{{ $item->id }}">{{ $item->name }}</option>
            @endforeach
        </select>
        <input type="search" wire:model.live.debounce.400ms="search" class="field max-w-xs text-sm" placeholder="Cari judul materi" aria-label="Cari materi">
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base text-[15px] [&_td]:py-4 [&_th]:py-3.5 [&_th]:text-[13px]">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Lokasi</th>
                        <th>Akses</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($materials as $material)
                        @php
                            $materialPayload = [
                                'title' => $material->title,
                                'location' => $material->chapter->subject->category->name.' › '.$material->chapter->subject->name.' › '.$material->chapter->title,
                                'access' => $material->is_free ? 'Gratis' : 'Langganan',
                                'status' => $material->published_at ? 'Terbit' : 'Draf',
                                'videoUrl' => $material->video_url,
                                'contentHtml' => Html::clean($material->content),
                                'editUrl' => route('admin.materials.edit', $material),
                            ];
                        @endphp
                        <tr wire:key="material-{{ $material->id }}">
                            <td class="text-base font-bold text-ink">{{ $material->title }}</td>
                            <td class="text-sm font-medium text-muted">{{ $material->chapter->subject->category->name }} › {{ $material->chapter->subject->name }} › {{ $material->chapter->title }}</td>
                            <td class="whitespace-nowrap">
                                @if ($material->is_free)
                                    <span class="badge bg-sun-100 px-3 py-1 text-sm text-sun-600">Gratis</span>
                                @else
                                    <span class="badge bg-navy-50 px-3 py-1 text-sm text-muted">Langganan</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap">
                                @if ($material->published_at)
                                    <span class="badge bg-brand-50 px-3 py-1 text-sm text-brand-700">Terbit</span>
                                @else
                                    <span class="badge bg-navy-50 px-3 py-1 text-sm text-muted">Draf</span>
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <button
                                    type="button"
                                    x-on:click="activeMaterial = {{ Js::from($materialPayload) }}"
                                    class="btn btn-outline btn-sm text-sm"
                                >
                                    Pratinjau
                                </button>
                                <a href="{{ route('admin.materials.edit', $material) }}" wire:navigate class="btn btn-ghost btn-sm text-sm">Ubah</a>
                                <button type="button" wire:click="delete({{ $material->id }})" wire:confirm="Hapus materi {{ $material->title }}?" class="btn btn-ghost btn-sm text-sm text-coral-600">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-base text-muted">Belum ada materi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $materials->links() }}</div>

    {{-- Modal pratinjau materi --}}
    <template x-teleport="body">
        <div
            x-cloak
            x-show="activeMaterial !== null"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4 backdrop-blur-xs"
            x-on:click.self="activeMaterial = null"
            role="dialog"
            aria-modal="true"
            aria-labelledby="material-modal-title"
        >
            <div x-show="activeMaterial !== null" x-transition class="flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-line bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-line px-5 py-4">
                    <div>
                        <h2 id="material-modal-title" class="text-lg font-extrabold text-ink" x-text="activeMaterial?.title"></h2>
                        <p class="mt-0.5 text-sm text-muted" x-text="activeMaterial?.location"></p>
                    </div>
                    <button type="button" x-on:click="activeMaterial = null" class="grid size-8 flex-none place-items-center rounded-lg text-lg text-subtle hover:bg-navy-50 hover:text-ink" aria-label="Tutup popup">×</button>
                </div>

                <div class="space-y-4 overflow-y-auto px-5 py-4">
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="badge bg-navy-50 px-2.5 py-1 text-ink" x-text="'Akses: ' + (activeMaterial?.access ?? '')"></span>
                        <span class="badge bg-brand-50 px-2.5 py-1 text-brand-700" x-text="'Status: ' + (activeMaterial?.status ?? '')"></span>
                        <a x-show="activeMaterial?.videoUrl" :href="activeMaterial?.videoUrl" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-700 hover:underline">Tautan video terlampir</a>
                    </div>
                    <div class="prose-materi text-[15px]" x-html="activeMaterial?.contentHtml"></div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-line bg-canvas/60 px-5 py-3.5">
                    <a :href="activeMaterial?.editUrl" wire:navigate x-on:click="activeMaterial = null" class="btn btn-outline btn-sm text-sm">Ubah materi</a>
                    <button type="button" x-on:click="activeMaterial = null" class="btn btn-primary btn-sm text-sm">Tutup</button>
                </div>
            </div>
        </div>
    </template>
</div>
