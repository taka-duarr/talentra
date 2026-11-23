<div>
    <x-page-header title="Belajar" description="Pilih kategori ujian untuk melihat materi per mata uji dan bab." />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($categories as $category)
            <a wire:key="category-{{ $category->id }}" href="{{ route('learn.category', $category) }}" wire:navigate
               class="card group relative overflow-hidden p-6 transition hover:border-brand-500">
                <span class="absolute inset-x-0 top-0 h-1" style="background: {{ $category->color }}"></span>
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-lg font-extrabold group-hover:text-brand-700">{{ $category->name }}</h2>
                    @if ($category->id === $targetId)
                        <span class="badge bg-navy-600 text-white">Tujuanmu</span>
                    @endif
                </div>
                <p class="mt-2 text-sm text-muted">{{ $category->description }}</p>
                <div class="mt-5 flex items-center gap-4 text-xs font-semibold text-subtle">
                    <span>{{ $category->subjects_count }} mata uji</span>
                    <span>{{ $category->exam_packages_count }} try out</span>
                    @if ($accessibleIds->contains($category->id))
                        <span class="ml-auto text-brand-700">✓ Termasuk langgananmu</span>
                    @endif
                </div>
            </a>
        @endforeach
    </div>
</div>
