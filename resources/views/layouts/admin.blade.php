<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}Admin Talentra</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans">
    <x-app-backdrop />

    @php
        $user = auth()->user();
        $isSuper = $user->isSuperAdmin();
        $groups = [
            'Umum' => [
                ['Ringkasan', 'admin.dashboard', 'admin.dashboard', 'grid', true],
                ['Hasil peserta', 'admin.attempts.index', 'admin.attempts.*', 'chart', true],
            ],
            'Konten' => [
                ['Kategori & mata uji', 'admin.categories', 'admin.categories*', 'layers', true],
                ['Materi', 'admin.materials.index', 'admin.materials.*', 'book', true],
                ['Bank soal', 'admin.questions.index', 'admin.questions.*', 'question', true],
                ['Paket try out', 'admin.packages.index', 'admin.packages.*', 'clipboard', true],
            ],
            'Komunitas' => [
                ['Moderasi forum', 'admin.moderation', 'admin.moderation', 'flag', true],
                ['Ranking', 'admin.ranking', 'admin.ranking', 'trophy', true],
            ],
            'Penjualan' => [
                ['Pesanan', 'admin.orders.index', 'admin.orders.*', 'receipt', $isSuper],
                ['Paket langganan', 'admin.plans', 'admin.plans*', 'package', $isSuper],
                ['Voucher', 'admin.vouchers', 'admin.vouchers*', 'tag', $isSuper],
                ['Pengguna', 'admin.users.index', 'admin.users.*', 'users', $isSuper],
            ],
        ];
        $groups = array_filter(array_map(fn ($items) => array_values(array_filter($items, fn ($item) => $item[4])), $groups));
        $localNow = now()->setTimezone('Asia/Jakarta');
    @endphp

    <div x-data="{ menuOpen: false }" class="flex w-full gap-3 p-2 sm:p-3 lg:gap-4 lg:p-4">
        {{-- Icon rail --}}
        <aside class="glass sticky top-4 z-30 hidden h-[calc(100vh-2rem)] w-[76px] flex-none flex-col items-center rounded-[28px] py-5 lg:flex" aria-label="Navigasi admin">
            <a href="{{ route('admin.dashboard') }}" wire:navigate class="grid size-11 place-items-center rounded-2xl bg-gradient-to-br from-navy-500 to-navy-700 text-white shadow-lg shadow-navy-600/30" aria-label="Ringkasan admin">
                <x-icon name="shield" class="size-6" />
            </a>
            <nav class="mt-6 flex flex-1 flex-col items-center gap-1.5">
                @foreach ($groups as $group => $items)
                    @unless ($loop->first)
                        <span class="my-1.5 h-px w-8 bg-navy-100" aria-hidden="true"></span>
                    @endunless
                    @foreach ($items as [$label, $route, $pattern, $icon])
                        <a href="{{ route($route) }}" wire:navigate class="rail-link" @if (request()->routeIs($pattern)) aria-current="page" @endif>
                            <x-icon :name="$icon" />
                            <span class="rail-tip">{{ $group }} · {{ $label }}</span>
                            <span class="sr-only">{{ $label }}</span>
                        </a>
                    @endforeach
                @endforeach
            </nav>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit" class="rail-link hover:text-coral-600">
                    <x-icon name="logout" />
                    <span class="rail-tip">Keluar</span>
                    <span class="sr-only">Keluar</span>
                </button>
            </form>
        </aside>

        {{-- Mobile drawer --}}
        <div x-cloak x-show="menuOpen" x-transition.opacity class="fixed inset-0 z-40 bg-ink/30 lg:hidden" @click="menuOpen = false"></div>
        <nav x-cloak x-show="menuOpen" x-transition:enter="transition duration-200" x-transition:enter-start="-translate-x-full" x-transition:leave="transition duration-150" x-transition:leave-end="-translate-x-full"
             class="fixed inset-y-3 left-3 z-50 w-64 overflow-y-auto rounded-3xl bg-white p-4 shadow-2xl lg:hidden" aria-label="Navigasi admin (seluler)">
            @foreach ($groups as $group => $items)
                <p class="px-3 pt-3 pb-1.5 text-[11px] font-bold tracking-wider text-subtle uppercase">{{ $group }}</p>
                @foreach ($items as [$label, $route, $pattern, $icon])
                    <a href="{{ route($route) }}" wire:navigate @class(['flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold', 'bg-brand-50 text-brand-700' => request()->routeIs($pattern), 'text-muted hover:bg-navy-50' => ! request()->routeIs($pattern)])>
                        <x-icon :name="$icon" class="size-4" /> {{ $label }}
                    </a>
                @endforeach
            @endforeach
        </nav>

        {{-- Main glass panel --}}
        <div class="glass min-w-0 flex-1 rounded-[28px] p-4 sm:p-6 lg:p-8">
            <header class="mb-7 flex items-center gap-3">
                <button type="button" class="glass grid size-10 flex-none place-items-center rounded-xl lg:hidden" @click="menuOpen = true" aria-label="Buka menu admin">
                    <x-icon name="menu" />
                </button>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-lg font-extrabold tracking-tight sm:text-xl">Panel admin <span class="ml-1 rounded-md bg-navy-600 px-1.5 py-0.5 align-middle text-[10px] font-bold text-white">{{ \Illuminate\Support\Str::upper($user->role->label()) }}</span></p>
                    <p class="truncate text-xs text-muted sm:text-sm">{{ $user->name }} · {{ $localNow->translatedFormat('l, d F Y') }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="lg:hidden">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm">Keluar</button>
                </form>
            </header>

            <main class="rise">
                <x-flash />
                {{ $slot }}
            </main>
        </div>
    </div>


    @stack('scripts')
</body>
</html>
