<div>
    <a href="{{ route('learn.index') }}" wire:navigate class="text-sm font-semibold text-muted hover:text-ink">← Semua kategori</a>
    <div class="mt-3">
        <x-page-header :title="$category->name" :description="$category->description">
            <x-slot:actions>
                <a href="{{ route('tryout.index', ['kategori' => $category->slug]) }}" wire:navigate class="btn btn-outline">Try out {{ $category->name }}</a>
            </x-slot:actions>
        </x-page-header>
    </div>

    @unless ($hasAccess)
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-line bg-white px-5 py-4">
            <p class="text-sm text-muted">Kamu bisa membaca materi bertanda <strong class="text-ink">Gratis</strong>. Materi lain terbuka dengan langganan {{ $category->name }} atau Semua Akses.</p>
            <a href="{{ route('plans.index') }}" wire:navigate class="btn btn-primary btn-sm">Buka dengan langganan</a>
        </div>
    @endunless

    <div class="space-y-6">
        @forelse ($category->subjects as $subject)
            <section wire:key="subject-{{ $subject->id }}" class="card overflow-hidden">
                <h2 class="border-b border-line bg-navy-50/40 px-5 py-4 font-extrabold">{{ $subject->name }}</h2>
                @forelse ($subject->chapters as $chapter)
                    <div wire:key="chapter-{{ $chapter->id }}" class="border-b border-line px-5 py-4 last:border-b-0">
                        <h3 class="text-sm font-bold text-subtle">Bab {{ $loop->iteration }} · {{ $chapter->title }}</h3>
                        <ul class="mt-2 divide-y divide-line">
                            @forelse ($chapter->materials as $material)
                                @php $unlocked = $hasAccess || $material->is_free; @endphp
                                <li wire:key="material-{{ $material->id }}">
                                    <a href="{{ route('learn.material', $material) }}" wire:navigate class="flex items-center gap-3 py-3 text-sm hover:text-brand-700">
                                        @if ($completedIds->contains($material->id))
                                            <span class="grid size-6 place-items-center rounded-full bg-brand-500 text-xs text-white" aria-label="Selesai">✓</span>
                                        @elseif ($unlocked)
                                            <span class="size-6 rounded-full border-2 border-line" aria-hidden="true"></span>
                                        @else
                                            <span class="grid size-6 place-items-center rounded-full bg-navy-50 text-subtle" aria-label="Terkunci">
                                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                                            </span>
                                        @endif
                                        <span @class(['flex-1 font-semibold', 'text-subtle' => ! $unlocked])>{{ $material->title }}</span>
                                        @if ($material->is_free)
                                            <span class="badge bg-sun-100 text-sun-600">Gratis</span>
                                        @endif
                                        @if ($material->video_url)
                                            <span class="badge bg-navy-50 text-muted">Video</span>
                                        @endif
                                    </a>
                                </li>
                            @empty
                                <li class="py-3 text-sm text-subtle">Materi sedang disiapkan.</li>
                            @endforelse
                        </ul>
                    </div>
                @empty
                    <p class="px-5 py-4 text-sm text-subtle">Materi untuk mata uji ini sedang disiapkan.</p>
                @endforelse
            </section>
        @empty
            <p class="card p-8 text-center text-muted">Belum ada materi di kategori ini.</p>
        @endforelse
    </div>
</div>
