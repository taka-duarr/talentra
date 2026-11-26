<div x-data="{ sidebarOpen: true, mobileDrawer: false }" class="space-y-4">
    {{-- Top bar kursus --}}
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-line bg-white px-4 py-3 shadow-2xs sm:px-5">
        <div class="flex min-w-0 items-center gap-3">
            <button
                type="button"
                @click="mobileDrawer = true"
                class="btn btn-outline btn-sm lg:hidden"
                aria-label="Buka daftar modul"
            >
                <x-icon name="menu" class="size-4" />
                Modul
            </button>
            <button
                type="button"
                x-cloak
                x-show="!sidebarOpen"
                @click="sidebarOpen = true"
                class="btn btn-outline btn-sm hidden lg:inline-flex"
            >
                <x-icon name="layers" class="size-4" />
                Tampilkan modul
            </button>

            <nav class="flex min-w-0 items-center gap-2 text-sm" aria-label="Navigasi kursus">
                <a href="{{ route('learn.index') }}" wire:navigate class="font-semibold text-muted hover:text-ink">Kursus</a>
                <span class="text-subtle">/</span>
                <a href="{{ route('learn.category', $category) }}" wire:navigate class="truncate font-extrabold text-ink hover:text-brand-700">{{ $category->name }}</a>
                @if ($activeMaterial)
                    <span class="hidden text-subtle sm:inline">/</span>
                    <span class="hidden truncate text-muted sm:inline">{{ $activeMaterial->chapter->subject->name }}</span>
                @elseif ($activePackage)
                    <span class="hidden text-subtle sm:inline">/</span>
                    <span class="hidden truncate text-muted sm:inline">Tes Try Out</span>
                @endif
            </nav>
        </div>

        <div class="flex items-center gap-2">
            @unless ($hasAccess)
                <a href="{{ route('plans.index') }}" wire:navigate class="btn btn-outline btn-sm border-brand-600 text-brand-700 hover:bg-brand-50">
                    Dapatkan akses penuh
                </a>
            @else
                <span class="badge bg-brand-50 px-3 py-1 text-xs text-brand-700">Akses penuh aktif</span>
            @endunless
        </div>
    </div>

    <div class="flex items-start gap-5">
        {{-- Mobile backdrop --}}
        <div x-cloak x-show="mobileDrawer" x-transition.opacity class="fixed inset-0 z-40 bg-ink/40 lg:hidden" @click="mobileDrawer = false"></div>

        {{-- Left Curriculum Sidebar (Coursera style) --}}
        <aside
            :class="[
                sidebarOpen ? 'lg:block' : 'lg:hidden',
                mobileDrawer ? 'fixed inset-y-3 left-3 z-50 block w-[310px] overflow-y-auto shadow-2xl' : 'hidden'
            ]"
            class="w-[320px] flex-none overflow-hidden rounded-2xl border border-line bg-white shadow-xs xl:w-[340px]"
            aria-label="Kurikulum kursus"
        >
            {{-- Course Header --}}
            <div class="flex items-center justify-between gap-2 border-b border-line px-4 py-3.5">
                <div class="min-w-0">
                    <p class="truncate text-base font-extrabold text-ink">{{ $category->name }}</p>
                    <p class="truncate text-xs text-muted">{{ $category->subjects->count() }} modul · {{ $packages->count() }} try out</p>
                </div>
                <button
                    type="button"
                    @click="sidebarOpen = false; mobileDrawer = false"
                    class="grid size-8 flex-none place-items-center rounded-lg text-lg text-subtle hover:bg-navy-50 hover:text-ink"
                    aria-label="Sembunyikan panel modul"
                >
                    ×
                </button>
            </div>

            {{-- Progress / Target Card (Coursera style) --}}
            <div x-data="{ openProgress: true }" class="border-b border-line p-3.5">
                <div class="rounded-xl border border-sky-200/70 bg-sky-50/70 p-3.5">
                    <button type="button" @click="openProgress = !openProgress" class="flex w-full items-center justify-between gap-2 text-left">
                        <span class="text-xs font-extrabold text-ink">Progres kursus · {{ $completedTrackable }}/{{ $totalTrackable }} item</span>
                        <svg class="size-4 text-muted transition" :class="openProgress ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </button>

                    <div x-show="openProgress" class="mt-2.5 space-y-2">
                        <div class="h-2 overflow-hidden rounded-full bg-white">
                            <div class="h-full rounded-full bg-brand-600 transition-all duration-300" style="width: {{ $progressPercent }}%"></div>
                        </div>
                        <p class="text-xs leading-relaxed text-muted">
                            @if ($uncompletedAccessibleMaterials->isNotEmpty())
                                Selesaikan <strong class="text-ink">{{ $uncompletedAccessibleMaterials->count() }} materi</strong> lagi untuk membuka kesiapan Tes Try Out.
                            @elseif ($packages->isNotEmpty())
                                Seluruh materi terbuka sudah selesai dipelajari. Lanjutkan ke <strong class="text-ink">Tes Try Out</strong> di bawah!
                            @else
                                Pelajari setiap modul secara berurutan agar tetap berada di jalur yang tepat.
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            {{-- Modules Accordion --}}
            <div class="max-h-[calc(100vh-15rem)] divide-y divide-line overflow-y-auto">
                @foreach ($category->subjects as $subject)
                    @php
                        $subjectMaterials = $subject->chapters->flatMap->materials;
                        $moduleLocked = ! $hasAccess && $subjectMaterials->isNotEmpty() && $subjectMaterials->where('is_free', true)->isEmpty();
                        $defaultOpen = ($activeSubjectId === $subject->id) || ($activeSubjectId === null && $activePackage === null && $loop->first);
                    @endphp
                    <div wire:key="course-mod-{{ $subject->id }}" x-data="{ open: {{ $defaultOpen ? 'true' : 'false' }} }">
                        <button
                            type="button"
                            @click="open = !open"
                            class="flex w-full items-start justify-between gap-3 px-4 py-3.5 text-left hover:bg-canvas/60"
                            :aria-expanded="open"
                        >
                            <div class="min-w-0">
                                <span class="block text-xs font-semibold text-brand-700">Modul {{ $loop->iteration }}</span>
                                <span class="mt-0.5 block text-sm font-extrabold text-ink">{{ $subject->name }}</span>
                            </div>
                            <div class="mt-1 flex flex-none items-center gap-1.5 text-subtle">
                                @if ($moduleLocked)
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-label="Terkunci"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                                @endif
                                <svg class="size-4 transition" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                            </div>
                        </button>

                        <div x-show="open" x-cloak class="pb-2">
                            @forelse ($subject->chapters as $chapter)
                                @if ($subject->chapters->count() > 1)
                                    <p class="px-4 pt-2 pb-1 text-[11px] font-bold tracking-wider text-subtle uppercase">{{ $chapter->title }}</p>
                                @endif
                                @forelse ($chapter->materials as $item)
                                    @php
                                        $itemUnlocked = $hasAccess || $item->is_free;
                                        $itemDone = $completedMaterialIds->contains($item->id);
                                        $isCurrent = $activeMaterial && $activeMaterial->id === $item->id;
                                    @endphp
                                    <a
                                        wire:key="nav-mat-{{ $item->id }}"
                                        href="{{ route('learn.material', $item) }}"
                                        wire:navigate
                                        @click="mobileDrawer = false"
                                        @class([
                                            'flex items-start gap-3 border-l-4 px-4 py-2.5 transition',
                                            'border-brand-600 bg-brand-50/70' => $isCurrent,
                                            'border-transparent hover:bg-canvas/80' => ! $isCurrent,
                                        ])
                                    >
                                        <span class="mt-0.5 flex-none">
                                            @if ($itemDone)
                                                <span class="grid size-5 place-items-center rounded-full bg-emerald-600 text-white" aria-label="Selesai">
                                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 5 5L20 7"/></svg>
                                                </span>
                                            @elseif ($itemUnlocked)
                                                <span @class(['block size-5 rounded-full border-2', 'border-brand-600 bg-white' => $isCurrent, 'border-navy-200' => ! $isCurrent]) aria-hidden="true"></span>
                                            @else
                                                <span class="grid size-5 place-items-center rounded-full bg-navy-50 text-subtle" aria-label="Terkunci">
                                                    <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                                                </span>
                                            @endif
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span @class(['block text-sm leading-snug', 'font-bold text-ink' => $isCurrent, 'font-medium text-ink' => ! $isCurrent && $itemUnlocked, 'font-medium text-muted' => ! $isCurrent && ! $itemUnlocked])>
                                                {{ $item->title }}
                                            </span>
                                            <span class="mt-0.5 block text-xs text-muted">
                                                {{ $item->kindLabel() }} · {{ $item->readingMinutes() }} mnt
                                                @if (! $hasAccess && $item->is_free)
                                                    · <strong class="text-brand-700">Gratis</strong>
                                                @endif
                                            </span>
                                        </span>
                                    </a>
                                @empty
                                    <p class="px-4 py-2 text-xs text-subtle">Materi belum tersedia di bab ini.</p>
                                @endforelse
                            @empty
                                <p class="px-4 py-2 text-xs text-subtle">Belum ada materi pada modul ini.</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach

                {{-- Final Evaluation Section: Tes Try Out (TO) --}}
                @if ($packages->isNotEmpty())
                    <div x-data="{ open: true }">
                        <button
                            type="button"
                            @click="open = !open"
                            class="flex w-full items-start justify-between gap-3 bg-navy-50/35 px-4 py-3.5 text-left hover:bg-navy-50/60"
                            :aria-expanded="open"
                        >
                            <div class="min-w-0">
                                <span class="block text-xs font-semibold text-brand-700">Tahap Evaluasi</span>
                                <span class="mt-0.5 block text-sm font-extrabold text-ink">Tes Try Out (TO)</span>
                            </div>
                            <div class="mt-1 flex flex-none items-center gap-1.5 text-subtle">
                                @unless ($areAccessibleMaterialsDone)
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" title="Selesaikan materi dulu"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                                @endunless
                                <svg class="size-4 transition" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                            </div>
                        </button>

                        <div x-show="open" class="pb-2">
                            @foreach ($packages as $pkg)
                                @php
                                    $pkgUnlocked = $pkg->isAccessibleBy(auth()->user());
                                    $pkgReady = $pkgUnlocked && $pkg->arePrerequisitesMetBy(auth()->user());
                                    $pkgDone = $submittedPackageIds->contains($pkg->id);
                                    $isPkgCurrent = $activePackage && $activePackage->id === $pkg->id;
                                @endphp
                                <a
                                    wire:key="nav-pkg-{{ $pkg->id }}"
                                    href="{{ route('learn.category', ['category' => $category, 'tryout' => $pkg->slug]) }}"
                                    wire:navigate
                                    @click="mobileDrawer = false"
                                    @class([
                                        'flex items-start gap-3 border-l-4 px-4 py-2.5 transition',
                                        'border-brand-600 bg-brand-50/70' => $isPkgCurrent,
                                        'border-transparent hover:bg-canvas/80' => ! $isPkgCurrent,
                                    ])
                                >
                                    <span class="mt-0.5 flex-none">
                                        @if ($pkgDone)
                                            <span class="grid size-5 place-items-center rounded-full bg-emerald-600 text-white" aria-label="Sudah dikerjakan">
                                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 5 5L20 7"/></svg>
                                            </span>
                                        @elseif ($pkgReady)
                                            <span @class(['grid size-5 place-items-center rounded-full border-2', 'border-brand-600 text-brand-700' => $isPkgCurrent, 'border-navy-200 text-muted' => ! $isPkgCurrent])>
                                                <x-icon name="clipboard" class="size-3" />
                                            </span>
                                        @else
                                            <span class="grid size-5 place-items-center rounded-full bg-navy-50 text-subtle" aria-label="Terkunci">
                                                <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                                            </span>
                                        @endif
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span @class(['block text-sm leading-snug', 'font-bold text-ink' => $isPkgCurrent, 'font-semibold text-ink' => ! $isPkgCurrent && $pkgReady, 'font-medium text-muted' => ! $isPkgCurrent && ! $pkgReady])>
                                            {{ $pkg->title }}
                                        </span>
                                        <span class="mt-0.5 block text-xs text-muted">
                                            Tes TO · {{ $pkg->duration_minutes }} mnt · {{ $pkg->sections->sum('questions_count') }} soal
                                            @if ($pkg->is_free)
                                                · <strong class="text-brand-700">Demo</strong>
                                            @endif
                                        </span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </aside>

        {{-- Right Main Workspace Stage --}}
        <div class="min-w-0 flex-1 rounded-2xl border border-line bg-white p-5 shadow-xs sm:p-8 lg:p-10">
            @if ($activeMaterial)
                @if ($materialUnlocked)
                    {{-- Unlocked Material Stage --}}
                    <div class="mx-auto max-w-3xl">
                        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-muted">
                            <span class="badge bg-navy-50 px-2.5 py-1 text-ink">{{ $activeMaterial->chapter->subject->name }}</span>
                            <span>·</span>
                            <span>{{ $activeMaterial->chapter->title }}</span>
                            <span>·</span>
                            <span>{{ $activeMaterial->kindLabel() }} {{ $activeMaterial->readingMinutes() }} menit</span>
                        </div>

                        <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-ink sm:text-3xl">{{ $activeMaterial->title }}</h1>

                        @if ($activeMaterial->embedUrl())
                            <div class="mt-6 aspect-video overflow-hidden rounded-2xl border border-line bg-ink">
                                <iframe src="{{ $activeMaterial->embedUrl() }}" title="Video {{ $activeMaterial->title }}" class="size-full" allowfullscreen loading="lazy"></iframe>
                            </div>
                        @endif

                        <article class="prose-materi mt-6 border-t border-line pt-6">
                            {!! $activeMaterial->content !!}
                        </article>

                        {{-- Progress & Next Step Bar --}}
                        <div class="mt-8 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-line bg-canvas/70 p-4 sm:p-5">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($materialCompleted)
                                    <span class="inline-flex items-center gap-1.5 text-sm font-bold text-emerald-700">
                                        <span class="grid size-5 place-items-center rounded-full bg-emerald-600 text-white">
                                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 5 5L20 7"/></svg>
                                        </span>
                                        Sudah dipelajari
                                    </span>
                                    <button type="button" wire:click="markIncomplete" class="btn btn-ghost btn-sm text-xs text-muted">Batalkan tanda selesai</button>
                                @else
                                    <button type="button" wire:click="completeAndAdvance" class="btn btn-primary">
                                        Tandai selesai & Lanjutkan →
                                    </button>
                                    <button type="button" wire:click="markComplete" class="btn btn-ghost btn-sm text-sm">
                                        Tandai selesai saja
                                    </button>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                @if ($previousMaterial)
                                    <a href="{{ route('learn.material', $previousMaterial) }}" wire:navigate class="btn btn-ghost btn-sm text-sm">← Sebelumnya</a>
                                @endif
                                @if ($nextMaterial)
                                    <a href="{{ route('learn.material', $nextMaterial) }}" wire:navigate class="btn btn-outline btn-sm text-sm">Berikutnya →</a>
                                @elseif ($nextTryoutAfterMaterials)
                                    <a href="{{ route('learn.category', ['category' => $category, 'tryout' => $nextTryoutAfterMaterials->slug]) }}" wire:navigate class="btn btn-primary btn-sm text-sm">
                                        Lanjut ke Tes Try Out →
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="mt-8">
                            <livewire:forum.material-discussion :material="$activeMaterial" :key="'discussion-'.$activeMaterial->id" />
                        </div>
                    </div>
                @else
                    {{-- Coursera-style Celebration / Paywall Screen for Locked Material --}}
                    <div class="mx-auto max-w-xl py-8 sm:py-12">
                        <div class="mx-auto grid size-20 rotate-3 place-items-center rounded-2xl bg-sky-50 text-navy-700 shadow-xs">
                            <x-icon name="trophy" class="size-10" />
                        </div>

                        <div class="mt-7">
                            @if ($completedFreeCount > 0)
                                <h1 class="text-2xl font-extrabold tracking-tight text-ink sm:text-3xl">
                                    Selamat, Anda telah menyelesaikan materi gratis pertama!
                                </h1>
                                <p class="mt-2 text-base text-muted">
                                    Materi <strong class="text-ink">{{ $activeMaterial->title }}</strong> adalah konten khusus pelanggan. Buka akses penuh untuk terus belajar di kursus {{ $category->name }}.
                                </p>
                            @else
                                <h1 class="text-2xl font-extrabold tracking-tight text-ink sm:text-3xl">
                                    Materi ini khusus pelanggan
                                </h1>
                                <p class="mt-2 text-base text-muted">
                                    Buka akses penuh untuk mempelajari <strong class="text-ink">{{ $activeMaterial->title }}</strong> dan seluruh modul di kursus {{ $category->name }}.
                                </p>
                            @endif

                            <ul class="mt-6 space-y-3 text-sm text-ink">
                                <li class="flex items-start gap-3">
                                    <x-icon name="check" class="mt-0.5 size-4 flex-none text-brand-700" />
                                    <span>Akses seluruh modul bacaan & video pembelajaran <strong class="font-semibold">{{ $category->name }}</strong> tanpa batas.</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <x-icon name="check" class="mt-0.5 size-4 flex-none text-brand-700" />
                                    <span>Buka semua paket <strong class="font-semibold">Tes Try Out (TO)</strong> lengkap dengan pembahasan per soal & analisis kelemahan AI.</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <x-icon name="check" class="mt-0.5 size-4 flex-none text-brand-700" />
                                    <span>Ikut papan peringkat peserta nasional dan forum diskusi tanya jawab materi.</span>
                                </li>
                            </ul>

                            <div class="mt-8 flex flex-wrap items-center gap-3">
                                <a href="{{ route('plans.index') }}" wire:navigate class="btn btn-primary px-6 py-3">
                                    Dapatkan akses penuh
                                </a>
                                @if ($demoPackage && $areAccessibleMaterialsDone)
                                    <a href="{{ route('learn.category', ['category' => $category, 'tryout' => $demoPackage->slug]) }}" wire:navigate class="btn btn-outline px-5 py-3">
                                        Coba Demo Try Out Gratis →
                                    </a>
                                @elseif ($uncompletedAccessibleMaterials->isNotEmpty())
                                    <a href="{{ route('learn.material', $uncompletedAccessibleMaterials->first()) }}" wire:navigate class="btn btn-ghost">
                                        Pelajari materi gratis dulu
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            @elseif ($activePackage && $packageState)
                {{-- Try Out (TO) Stage Inside Course Player --}}
                <div class="mx-auto max-w-3xl">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                        <span class="badge bg-brand-50 px-2.5 py-1 text-brand-700">Tahap Evaluasi · Tes Try Out</span>
                        @if ($activePackage->is_free)
                            <span class="badge bg-sun-100 px-2.5 py-1 text-sun-600">Demo Gratis</span>
                        @endif
                    </div>

                    <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-ink sm:text-3xl">{{ $activePackage->title }}</h1>
                    <p class="mt-2 text-base text-muted">{{ $activePackage->description }}</p>

                    <dl class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div class="rounded-xl border border-line bg-canvas/60 p-4">
                            <dt class="text-xs font-semibold text-subtle">Jumlah Soal</dt>
                            <dd class="mt-1 text-xl font-extrabold text-ink">{{ $packageState['questionCount'] }}</dd>
                        </div>
                        <div class="rounded-xl border border-line bg-canvas/60 p-4">
                            <dt class="text-xs font-semibold text-subtle">Durasi</dt>
                            <dd class="mt-1 text-xl font-extrabold text-ink">{{ $activePackage->duration_minutes }} mnt</dd>
                        </div>
                        <div class="rounded-xl border border-line bg-canvas/60 p-4">
                            <dt class="text-xs font-semibold text-subtle">Skor Benar / Salah</dt>
                            <dd class="mt-1 text-xl font-extrabold text-ink">+{{ $activePackage->score_correct }} / {{ $activePackage->score_wrong }}</dd>
                        </div>
                        <div class="rounded-xl border border-line bg-canvas/60 p-4">
                            <dt class="text-xs font-semibold text-subtle">Sisa Kesempatan</dt>
                            <dd class="mt-1 text-xl font-extrabold text-ink">{{ $packageState['attemptsLeft'] ?? 'Tanpa batas' }}</dd>
                        </div>
                    </dl>

                    @if (! $packageState['unlocked'])
                        {{-- Locked behind subscription --}}
                        <div class="mt-6 rounded-2xl border border-line bg-canvas/70 p-6">
                            <h2 class="text-lg font-extrabold text-ink">Tes Try Out ini khusus pelanggan</h2>
                            <p class="mt-1 text-sm text-muted">Aktifkan paket langganan {{ $category->name }} atau Semua Akses untuk mengerjakan simulasi Try Out penuh beserta analisis AI.</p>
                            <a href="{{ route('plans.index') }}" wire:navigate class="btn btn-primary mt-4">Dapatkan akses penuh</a>
                        </div>
                    @elseif (! $packageState['prerequisitesMet'] && ! $packageState['running'])
                        {{-- Locked behind uncompleted course materials! --}}
                        <div class="mt-6 rounded-2xl border border-sun-200 bg-sun-100/40 p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h2 class="text-lg font-extrabold text-ink">Selesaikan materi kursus terlebih dahulu</h2>
                                    <p class="mt-1 text-sm text-muted">
                                        Sesuai alur belajar, kamu perlu menyelesaikan seluruh materi yang terbuka di kursus <strong class="text-ink">{{ $category->name }}</strong> sebelum memulai Tes Try Out ini ({{ $packageState['completedPrerequisites'] }}/{{ $packageState['totalPrerequisites'] }} selesai).
                                    </p>
                                </div>
                            </div>

                            <ul class="mt-4 divide-y divide-line rounded-xl border border-line bg-white">
                                @foreach ($packageState['uncompletedMaterials']->take(5) as $remMat)
                                    <li class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                                        <span class="font-medium text-ink">{{ $remMat->title }}</span>
                                        <a href="{{ route('learn.material', $remMat) }}" wire:navigate class="font-semibold whitespace-nowrap text-brand-700 hover:underline">Pelajari →</a>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="mt-5">
                                <a href="{{ route('learn.material', $packageState['uncompletedMaterials']->first()) }}" wire:navigate class="btn btn-primary">
                                    Lanjutkan materi: {{ $packageState['uncompletedMaterials']->first()->title }} →
                                </a>
                            </div>
                        </div>
                    @else
                        {{-- Ready to Start Try Out! --}}
                        <div class="mt-6 space-y-5">
                            <div>
                                <h2 class="text-base font-bold text-ink">Bagian ujian</h2>
                                <ul class="mt-2.5 divide-y divide-line rounded-xl border border-line">
                                    @foreach ($activePackage->sections as $section)
                                        <li wire:key="pkg-sec-{{ $section->id }}" class="flex items-center justify-between px-4 py-3 text-sm">
                                            <span class="font-semibold text-ink">{{ $section->name }}</span>
                                            <span class="text-muted">{{ $section->questions_count }} soal @if ($section->passing_score) · ambang batas {{ $section->passing_score }} @endif</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="rounded-xl bg-navy-50/60 p-4 text-sm text-muted">
                                <p class="font-semibold text-ink">Ketentuan pengerjaan</p>
                                <ul class="mt-2 list-disc space-y-1 pl-5">
                                    <li>Timer berjalan di server dan jawaban tersimpan otomatis setiap memilih opsi.</li>
                                    <li>Soal dapat ditandai ragu-ragu dan ditinjau ulang sebelum klik selesai.</li>
                                    @if ($activePackage->counts_for_ranking)
                                        <li>Hanya percobaan pertama yang dihitung pada papan peringkat.</li>
                                    @endif
                                </ul>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                @if ($packageState['running'])
                                    <a href="{{ route('exam.show', $packageState['running']) }}" class="btn btn-navy px-6 py-3">
                                        Lanjutkan ujian (sisa {{ ceil($packageState['running']->remainingSeconds() / 60) }} menit)
                                    </a>
                                @elseif ($packageState['attemptsLeft'] === 0)
                                    <p class="text-sm font-semibold text-coral-600">Kesempatan mengerjakan paket ini sudah habis.</p>
                                @else
                                    <form method="POST" action="{{ route('exam.start', $activePackage) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary px-6 py-3">
                                            {{ $packageState['attempts']->isEmpty() ? 'Mulai Tes Try Out Sekarang' : 'Kerjakan Ulang Try Out' }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($packageState['attempts']->where('status.value', 'submitted')->isNotEmpty())
                        <section class="mt-8 overflow-hidden rounded-2xl border border-line">
                            <h2 class="border-b border-line bg-canvas/60 px-5 py-3.5 text-sm font-bold text-ink">Riwayat percobaanmu</h2>
                            <ul class="divide-y divide-line">
                                @foreach ($packageState['attempts']->where('status.value', 'submitted') as $attempt)
                                    <li wire:key="course-att-{{ $attempt->id }}" class="flex items-center justify-between px-5 py-3.5 text-sm">
                                        <span class="text-muted">
                                            {{ $attempt->submitted_at->translatedFormat('d M Y, H:i') }}
                                            @if ($attempt->is_first_attempt)
                                                <span class="badge ml-1 bg-navy-50 text-muted">Percobaan pertama</span>
                                            @endif
                                        </span>
                                        <span class="flex items-center gap-4">
                                            <strong class="text-base text-ink">{{ $attempt->percentage() }}%</strong>
                                            <a href="{{ route('results.show', $attempt) }}" wire:navigate class="font-semibold text-brand-700 hover:underline">Lihat Hasil & Analisis AI →</a>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                </div>
            @else
                <div class="py-14 text-center">
                    <p class="text-base font-semibold text-ink">Materi dan Try Out untuk kursus {{ $category->name }} sedang disiapkan.</p>
                    <a href="{{ route('learn.index') }}" wire:navigate class="btn btn-outline mt-4">Pilih kursus lain</a>
                </div>
            @endif
        </div>
    </div>
</div>
