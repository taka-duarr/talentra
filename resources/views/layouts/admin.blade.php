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
    @php
        $isSuper = auth()->user()->isSuperAdmin();
        $groups = [
            'Umum' => [
                ['Ringkasan', 'admin.dashboard', 'admin.dashboard', true],
            ],
            'Konten' => [
                ['Kategori & mata uji', 'admin.categories', 'admin.categories*', true],
                ['Materi', 'admin.materials.index', 'admin.materials.*', true],
                ['Bank soal', 'admin.questions.index', 'admin.questions.*', true],
                ['Paket try out', 'admin.packages.index', 'admin.packages.*', true],
            ],
            'Komunitas' => [
                ['Moderasi forum', 'admin.moderation', 'admin.moderation', true],
                ['Ranking', 'admin.ranking', 'admin.ranking', true],
            ],
            'Penjualan' => [
                ['Pesanan', 'admin.orders.index', 'admin.orders.*', $isSuper],
                ['Paket langganan', 'admin.plans', 'admin.plans*', $isSuper],
                ['Voucher', 'admin.vouchers', 'admin.vouchers*', $isSuper],
            ],
            'Pengguna' => [
                ['Pengguna', 'admin.users.index', 'admin.users.*', $isSuper],
            ],
        ];
    @endphp

    <div x-data="{ sidebarOpen: false }" class="lg:flex">
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-line bg-white transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
            <div class="flex h-16 items-center border-b border-line px-5">
                <x-brand :href="route('admin.dashboard')" />
                <span class="ml-2 rounded-md bg-navy-50 px-1.5 py-0.5 text-[11px] font-bold text-navy-600">ADMIN</span>
            </div>
            <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5" aria-label="Navigasi admin">
                @foreach ($groups as $group => $items)
                    @php $visible = array_filter($items, fn ($item) => $item[3]); @endphp
                    @if ($visible)
                        <div>
                            <p class="px-3 pb-2 text-[11px] font-bold tracking-wider text-subtle uppercase">{{ $group }}</p>
                            @foreach ($visible as [$label, $route, $pattern])
                                <a href="{{ route($route) }}" wire:navigate
                                   @class([
                                       'block rounded-lg px-3 py-2 text-sm font-semibold',
                                       'bg-brand-50 text-brand-700' => request()->routeIs($pattern),
                                       'text-muted hover:bg-navy-50 hover:text-ink' => ! request()->routeIs($pattern),
                                   ])>{{ $label }}</a>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </nav>
            <div class="border-t border-line p-3">
                <a href="{{ route('dashboard') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-muted hover:bg-navy-50">← Kembali ke aplikasi</a>
            </div>
        </aside>
        <div x-cloak x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-ink/30 lg:hidden"></div>

        <div class="min-w-0 flex-1">
            <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-line bg-white/90 px-4 backdrop-blur sm:px-6">
                <button type="button" class="btn btn-ghost lg:hidden" @click="sidebarOpen = true" aria-label="Buka menu admin">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
                <span class="text-sm text-muted">Masuk sebagai <strong class="text-ink">{{ auth()->user()->name }}</strong> · {{ auth()->user()->role->label() }}</span>
                <form method="POST" action="{{ route('logout') }}" class="ml-auto">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm">Keluar</button>
                </form>
            </header>
            <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
                <x-flash />
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
