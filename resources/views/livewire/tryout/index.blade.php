<div>
    <x-page-header title="Try Out" description="Simulasi ujian berbasis komputer dengan timer, navigasi soal, dan analisis hasil." />

    <div class="mb-6 flex gap-2 overflow-x-auto pb-1" role="tablist" aria-label="Filter kategori">
        <button type="button" wire:click="$set('category', '')" role="tab" aria-selected="{{ $category === '' ? 'true' : 'false' }}"
                @class(['btn btn-sm whitespace-nowrap', 'btn-navy' => $category === '', 'btn-outline' => $category !== ''])>Semua</button>
        @foreach ($categories as $item)
            <button type="button" wire:key="filter-{{ $item->id }}" wire:click="$set('category', '{{ $item->slug }}')" role="tab" aria-selected="{{ $category === $item->slug ? 'true' : 'false' }}"
                    @class(['btn btn-sm whitespace-nowrap', 'btn-navy' => $category === $item->slug, 'btn-outline' => $category !== $item->slug])>{{ $item->name }}</button>
        @endforeach
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($packages as $package)
            @php
                $questionCount = $package->sections->sum('questions_count');
                $unlocked = $package->is_free || $accessibleIds->contains($package->category_id);
                $used = $attemptCounts[$package->id] ?? 0;
            @endphp
            <a wire:key="package-{{ $package->id }}" href="{{ route('tryout.show', $package) }}" wire:navigate class="card group flex flex-col p-5 transition hover:border-brand-500">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-subtle">{{ $package->category->name }}</span>
                    @if ($package->is_free)
                        <span class="badge bg-sun-100 text-sun-600">Demo gratis</span>
                    @elseif (! $unlocked)
                        <span class="badge bg-navy-50 text-muted">Langganan</span>
                    @endif
                    @if ($used > 0)
                        <span class="badge ml-auto bg-brand-50 text-brand-700">Dikerjakan {{ $used }}×</span>
                    @endif
                </div>
                <h2 class="mt-2 text-lg font-bold group-hover:text-brand-700">{{ $package->title }}</h2>
                <p class="mt-1 line-clamp-2 text-sm text-muted">{{ $package->description }}</p>
                <dl class="mt-4 flex flex-wrap gap-x-5 gap-y-1 text-sm">
                    <div><dt class="sr-only">Jumlah soal</dt><dd class="font-semibold">{{ $questionCount }} soal</dd></div>
                    <div><dt class="sr-only">Durasi</dt><dd class="font-semibold">{{ $package->duration_minutes }} menit</dd></div>
                    <div><dt class="sr-only">Bagian</dt><dd class="text-muted">{{ $package->sections->pluck('name')->join(' · ') }}</dd></div>
                </dl>
            </a>
        @empty
            <p class="card p-8 text-center text-muted md:col-span-2">Belum ada try out di kategori ini.</p>
        @endforelse
    </div>
</div>
