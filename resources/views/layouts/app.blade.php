<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}Talentra</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans">
    @php
        $navigation = [
            ['label' => 'Beranda', 'route' => 'dashboard', 'active' => 'dashboard'],
            ['label' => 'Belajar', 'route' => 'learn.index', 'active' => 'learn.*'],
            ['label' => 'Try Out', 'route' => 'tryout.index', 'active' => 'tryout.*'],
            ['label' => 'Forum', 'route' => 'forum.index', 'active' => 'forum.*'],
            ['label' => 'Ranking', 'route' => 'ranking.index', 'active' => 'ranking.*'],
            ['label' => 'Langganan', 'route' => 'plans.index', 'active' => 'plans.*'],
        ];
    @endphp

    <header x-data="{ menuOpen: false }" class="sticky top-0 z-30 border-b border-line bg-white/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-6 px-4">
            <x-brand :href="route('dashboard')" />

            <nav class="hidden items-center gap-1 md:flex" aria-label="Navigasi utama">
                @foreach ($navigation as $item)
                    <a href="{{ route($item['route']) }}" wire:navigate
                       @class([
                           'rounded-lg px-3 py-2 text-sm font-semibold transition',
                           'bg-brand-50 text-brand-700' => request()->routeIs($item['active']),
                           'text-muted hover:text-ink' => ! request()->routeIs($item['active']),
                       ])>{{ $item['label'] }}</a>
                @endforeach
            </nav>

            <div class="ml-auto flex items-center gap-2">
                @auth
                    <livewire:notifications.notification-bell />
                    <div x-data="{ open: false }" class="relative" @click.outside="open = false">
                        <button type="button" @click="open = ! open" class="flex items-center gap-2 rounded-xl px-2 py-1.5 hover:bg-navy-50" :aria-expanded="open">
                            <span class="grid size-8 place-items-center rounded-full bg-navy-600 text-sm font-bold text-white">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name, 0, 1)) }}</span>
                            <span class="hidden max-w-32 truncate text-sm font-semibold sm:block">{{ auth()->user()->publicName() }}</span>
                            <svg class="size-4 text-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div x-cloak x-show="open" x-transition.origin.top.right class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-line bg-white py-1 shadow-lg">
                            <a href="{{ route('profile.edit') }}" wire:navigate class="block px-4 py-2 text-sm hover:bg-navy-50">Profil & pengaturan</a>
                            <a href="{{ route('orders.index') }}" wire:navigate class="block px-4 py-2 text-sm hover:bg-navy-50">Pesanan saya</a>
                            @if (auth()->user()->isAdmin())
                                <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm font-semibold text-brand-700 hover:bg-navy-50">Dashboard admin</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}" class="border-t border-line">
                                @csrf
                                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-coral-600 hover:bg-coral-100/50">Keluar</button>
                            </form>
                        </div>
                    </div>
                @endauth

                <button type="button" class="btn btn-ghost md:hidden" @click="menuOpen = ! menuOpen" aria-label="Buka menu">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>
        </div>

        <nav x-cloak x-show="menuOpen" class="border-t border-line px-4 py-2 md:hidden" aria-label="Navigasi utama (seluler)">
            @foreach ($navigation as $item)
                <a href="{{ route($item['route']) }}" wire:navigate
                   @class([
                       'block rounded-lg px-3 py-2.5 text-sm font-semibold',
                       'bg-brand-50 text-brand-700' => request()->routeIs($item['active']),
                       'text-muted' => ! request()->routeIs($item['active']),
                   ])>{{ $item['label'] }}</a>
            @endforeach
        </nav>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8 sm:py-10">
        <x-flash />
        {{ $slot }}
    </main>
</body>
</html>
