<div class="mx-auto max-w-3xl">
    @php $category = $material->chapter->subject->category; @endphp
    <nav class="text-sm text-muted" aria-label="Breadcrumb">
        <a href="{{ route('learn.category', $category) }}" wire:navigate class="font-semibold hover:text-ink">{{ $category->name }}</a>
        <span class="mx-1.5">/</span>{{ $material->chapter->subject->name }}
        <span class="mx-1.5">/</span>{{ $material->chapter->title }}
    </nav>

    <h1 class="mt-3 text-3xl font-extrabold tracking-tight">{{ $material->title }}</h1>

    @if ($unlocked)
        @if ($material->embedUrl())
            <div class="mt-6 aspect-video overflow-hidden rounded-2xl border border-line bg-ink">
                <iframe src="{{ $material->embedUrl() }}" title="Video {{ $material->title }}" class="size-full" allowfullscreen loading="lazy"></iframe>
            </div>
        @endif

        <article class="prose-materi card mt-6 p-6 sm:p-8">{!! $material->content !!}</article>

        <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
            @if ($completed)
                <button type="button" wire:click="markIncomplete" class="btn btn-outline">✓ Sudah dipelajari · batalkan</button>
            @else
                <button type="button" wire:click="markComplete" class="btn btn-primary">Tandai sudah dipelajari</button>
            @endif
            <div class="flex gap-2">
                @if ($previous)
                    <a href="{{ route('learn.material', $previous) }}" wire:navigate class="btn btn-ghost">← Sebelumnya</a>
                @endif
                @if ($next)
                    <a href="{{ route('learn.material', $next) }}" wire:navigate class="btn btn-outline">Berikutnya →</a>
                @endif
            </div>
        </div>

        <livewire:forum.material-discussion :material="$material" :key="'discussion-'.$material->id" />
    @else
        <div class="card mt-6 p-8 text-center">
            <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-navy-50 text-navy-600">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
            </div>
            <h2 class="mt-4 text-lg font-bold">Materi ini khusus pelanggan</h2>
            <p class="mx-auto mt-2 max-w-md text-sm text-muted">Buka semua materi dan try out {{ $category->name }} dengan langganan {{ $category->name }} atau Semua Akses.</p>
            <a href="{{ route('plans.index') }}" wire:navigate class="btn btn-primary mt-6">Lihat paket langganan</a>
        </div>
    @endif
</div>
