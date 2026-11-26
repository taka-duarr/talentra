@php use Illuminate\Support\Js; @endphp
<div x-data="{ activePackage: null }" @keydown.escape.window="activePackage = null">
    <x-page-header title="Paket try out">
        <x-slot:actions>
            <a href="{{ route('admin.packages.create') }}" wire:navigate class="btn btn-primary">+ Paket baru</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base text-[15px] [&_td]:py-4 [&_th]:py-3.5 [&_th]:text-[13px]">
                <thead>
                    <tr>
                        <th>Paket</th>
                        <th>Kategori</th>
                        <th>Bagian & Soal</th>
                        <th>Durasi</th>
                        <th>Dikerjakan</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($packages as $package)
                        @php
                            $totalQuestions = $package->sections->sum('questions_count');
                            $packagePayload = [
                                'title' => $package->title,
                                'category' => $package->category->name,
                                'isFree' => (bool) $package->is_free,
                                'duration' => $package->duration_minutes.' menit',
                                'attempts' => $package->attempts_count.'× dikerjakan',
                                'totalQuestions' => $totalQuestions,
                                'editUrl' => route('admin.packages.edit', $package),
                                'sections' => $package->sections->map(fn ($section) => [
                                    'name' => $section->name,
                                    'questions' => $section->questions_count.' soal',
                                    'passingGrade' => $section->passing_grade !== null ? 'Passing grade '.$section->passing_grade : null,
                                ])->values()->all(),
                            ];
                        @endphp
                        <tr wire:key="package-{{ $package->id }}">
                            <td>
                                <span class="text-base font-bold text-ink">{{ $package->title }}</span>
                                @if ($package->is_free)
                                    <span class="badge ml-1.5 bg-sun-100 px-2.5 py-0.5 text-xs text-sun-600">Demo</span>
                                @endif
                            </td>
                            <td class="text-sm font-medium text-muted">{{ $package->category->name }}</td>
                            <td class="whitespace-nowrap">
                                <button
                                    type="button"
                                    x-on:click="activePackage = {{ Js::from($packagePayload) }}"
                                    class="btn btn-outline btn-sm text-sm"
                                >
                                    Lihat bagian ({{ $package->sections->count() }} · {{ $totalQuestions }} soal)
                                </button>
                            </td>
                            <td class="text-sm font-medium whitespace-nowrap text-ink">{{ $package->duration_minutes }} mnt</td>
                            <td class="text-sm font-medium whitespace-nowrap text-muted">{{ $package->attempts_count }}×</td>
                            <td>
                                <span class="badge px-3 py-1 text-sm {{ $package->status->badgeClass() }}">{{ $package->status->label() }}</span>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.packages.edit', $package) }}" wire:navigate class="btn btn-ghost btn-sm text-sm">Kelola</a>
                                <button type="button" wire:click="delete({{ $package->id }})" wire:confirm="Hapus paket {{ $package->title }}?" class="btn btn-ghost btn-sm text-sm text-coral-600">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-base text-muted">Belum ada paket.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal daftar bagian soal --}}
    <template x-teleport="body">
        <div
            x-cloak
            x-show="activePackage !== null"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4 backdrop-blur-xs"
            x-on:click.self="activePackage = null"
            role="dialog"
            aria-modal="true"
            aria-labelledby="package-modal-title"
        >
            <div x-show="activePackage !== null" x-transition class="w-full max-w-md overflow-hidden rounded-2xl border border-line bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-line px-5 py-4">
                    <div>
                        <h2 id="package-modal-title" class="text-lg font-extrabold text-ink" x-text="activePackage?.title"></h2>
                        <p class="mt-0.5 text-sm text-muted" x-text="(activePackage?.category ?? '') + ' · ' + (activePackage?.duration ?? '') + ' · ' + (activePackage?.attempts ?? '')"></p>
                    </div>
                    <button type="button" x-on:click="activePackage = null" class="grid size-8 flex-none place-items-center rounded-lg text-lg text-subtle hover:bg-navy-50 hover:text-ink" aria-label="Tutup popup">×</button>
                </div>

                <div class="divide-y divide-line px-5">
                    <template x-for="(section, idx) in (activePackage?.sections ?? [])" :key="idx">
                        <div class="flex items-center justify-between gap-4 py-3.5">
                            <div>
                                <p class="text-base font-bold text-ink" x-text="section.name"></p>
                                <p x-show="section.passingGrade" class="text-sm text-muted" x-text="section.passingGrade"></p>
                            </div>
                            <span class="badge bg-navy-50 px-3 py-1 text-sm text-ink" x-text="section.questions"></span>
                        </div>
                    </template>
                    <p x-show="activePackage && activePackage.sections.length === 0" class="py-6 text-center text-sm text-muted">
                        Belum ada bagian soal pada paket ini.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-line bg-canvas/60 px-5 py-3.5">
                    <a :href="activePackage?.editUrl" wire:navigate x-on:click="activePackage = null" class="btn btn-outline btn-sm text-sm">Kelola paket</a>
                    <button type="button" x-on:click="activePackage = null" class="btn btn-primary btn-sm text-sm">Tutup</button>
                </div>
            </div>
        </div>
    </template>
</div>
