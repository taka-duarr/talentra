<div>
    <x-page-header title="Kursus & Persiapan Ujian" description="Pelajari modul materi secara bertahap, lalu uji kemampuanmu lewat Tes Try Out di akhir kursus." />

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($categories as $category)
            @php
                $prog = $progressByCategory[$category->id] ?? ['total' => 0, 'done' => 0, 'percent' => 0];
                $hasSub = $accessibleIds->contains($category->id);
            @endphp
            <a
                wire:key="category-{{ $category->id }}"
                href="{{ route('learn.category', $category) }}"
                wire:navigate
                class="card group relative flex flex-col overflow-hidden p-6 transition hover:border-brand-500"
            >
                <span class="absolute inset-x-0 top-0 h-1.5" style="background: {{ $category->color }}"></span>

                <div class="flex items-start justify-between gap-3">
                    <div>
                        <span class="text-xs font-bold tracking-wide text-subtle uppercase">Kursus</span>
                        <h2 class="mt-0.5 text-lg font-extrabold text-ink group-hover:text-brand-700">{{ $category->name }}</h2>
                    </div>
                    @if ($category->id === $targetId)
                        <span class="badge bg-navy-600 text-white">Tujuanmu</span>
                    @elseif ($hasSub)
                        <span class="badge bg-brand-50 text-brand-700">Aktif</span>
                    @endif
                </div>

                <p class="mt-2 flex-1 text-sm text-muted">{{ $category->description }}</p>

                <div class="mt-5 space-y-2.5 border-t border-line pt-4">
                    <div class="flex items-center justify-between text-xs font-semibold text-muted">
                        <span>{{ $category->subjects_count }} modul · {{ $prog['total'] }} materi · {{ $category->exam_packages_count }} try out</span>
                        @if ($prog['total'] > 0)
                            <span class="text-ink">{{ $prog['percent'] }}%</span>
                        @endif
                    </div>

                    @if ($prog['total'] > 0)
                        <div class="h-1.5 overflow-hidden rounded-full bg-navy-50">
                            <div class="h-full rounded-full bg-brand-600 transition-all" style="width: {{ $prog['percent'] }}%"></div>
                        </div>
                    @endif

                    <div class="flex items-center justify-between pt-1 text-sm font-bold text-brand-700">
                        <span>{{ $prog['done'] > 0 ? 'Lanjutkan kursus' : 'Mulai belajar' }} →</span>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</div>
