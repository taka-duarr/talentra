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
    <x-app-backdrop />

    @php
        $user = auth()->user();
        $navigation = [
            ['label' => 'Beranda', 'route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'home'],
            ['label' => 'Belajar', 'route' => 'learn.index', 'active' => 'learn.*', 'icon' => 'book'],
            ['label' => 'Try Out', 'route' => 'tryout.index', 'active' => ['tryout.*', 'results.*'], 'icon' => 'clipboard'],
            ['label' => 'Forum', 'route' => 'forum.index', 'active' => 'forum.*', 'icon' => 'chat'],
            ['label' => 'Ranking', 'route' => 'ranking.index', 'active' => 'ranking.*', 'icon' => 'trophy'],
            ['label' => 'Langganan', 'route' => 'plans.index', 'active' => ['plans.*', 'orders.*'], 'icon' => 'card'],
        ];
        $localNow = now()->setTimezone('Asia/Jakarta');
        $hour = (int) $localNow->format('G');
        $greeting = match (true) {
            $hour < 11 => 'Selamat pagi',
            $hour < 15 => 'Selamat siang',
            $hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };
        $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user?->name ?? 'T', 0, 1));
    @endphp

    <div class="flex w-full gap-3 p-2 pb-28 sm:p-3 sm:pb-28 lg:gap-4 lg:p-4">
        {{-- Icon rail --}}
        <aside class="glass sticky top-4 hidden h-[calc(100vh-2rem)] w-[76px] flex-none flex-col items-center rounded-[28px] py-5 lg:flex" aria-label="Navigasi utama">
            <a href="{{ route('dashboard') }}" wire:navigate class="grid size-11 place-items-center rounded-2xl bg-gradient-to-br from-brand-500 to-navy-600 text-white shadow-lg shadow-brand-500/30" aria-label="Talentra, ke beranda">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h11"/><path d="M9.5 6v13"/><path d="m14 15 5-5"/><path d="M15.5 10H19v3.5"/></svg>
            </a>

            <nav class="mt-8 flex flex-1 flex-col items-center gap-2">
                @foreach ($navigation as $item)
                    <a href="{{ route($item['route']) }}" wire:navigate class="rail-link" @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                        <x-icon :name="$item['icon']" />
                        <span class="rail-tip">{{ $item['label'] }}</span>
                        <span class="sr-only">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="flex flex-col items-center gap-2">
                <a href="{{ route('profile.edit') }}" wire:navigate class="rail-link" @if (request()->routeIs('profile.*')) aria-current="page" @endif>
                    <x-icon name="user" />
                    <span class="rail-tip">Profil & pengaturan</span>
                    <span class="sr-only">Profil & pengaturan</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rail-link hover:text-coral-600">
                        <x-icon name="logout" />
                        <span class="rail-tip">Keluar</span>
                        <span class="sr-only">Keluar</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main glass panel --}}
        <div class="glass min-w-0 flex-1 rounded-[28px] p-4 sm:p-6 lg:p-8">
            <header class="mb-7 flex items-center gap-3">
                <a href="{{ route('dashboard') }}" wire:navigate class="grid size-10 flex-none place-items-center rounded-xl bg-gradient-to-br from-brand-500 to-navy-600 text-white lg:hidden" aria-label="Talentra, ke beranda">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h11"/><path d="M9.5 6v13"/><path d="m14 15 5-5"/><path d="M15.5 10H19v3.5"/></svg>
                </a>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-lg font-extrabold tracking-tight sm:text-xl">{{ $greeting }}, {{ $user?->publicName() }} <span class="inline-block origin-bottom-right animate-[float-soft_3s_ease-in-out_infinite]" aria-hidden="true">👋</span></p>
                    <p class="truncate text-xs text-muted sm:text-sm">{{ $localNow->translatedFormat('l, d F Y') }} · Ayo lanjutkan latihanmu hari ini</p>
                </div>

                @auth
                    <div class="glass flex items-center gap-1 rounded-2xl p-1">
                        <livewire:notifications.notification-bell />
                        <div x-data="{ open: false }" class="relative" @click.outside="open = false">
                            <button type="button" @click="open = ! open" class="flex items-center gap-2 rounded-xl px-1.5 py-1 hover:bg-white" :aria-expanded="open" aria-label="Menu akun">
                                <span class="grid size-8 place-items-center rounded-full bg-navy-600 text-sm font-bold text-white">{{ $initial }}</span>
                                <x-icon name="chevron-down" class="hidden size-4 text-subtle sm:block" />
                            </button>
                            <div x-cloak x-show="open" x-transition.origin.top.right class="absolute right-0 z-40 mt-2 w-60 overflow-hidden rounded-2xl border border-line bg-white py-1 shadow-xl">
                                <p class="border-b border-line px-4 py-3 text-sm"><span class="block font-bold">{{ $user->publicName() }}</span><span class="block truncate text-xs text-subtle">{{ $user->email }}</span></p>
                                <a href="{{ route('profile.edit') }}" wire:navigate class="block px-4 py-2 text-sm hover:bg-navy-50">Profil & pengaturan</a>
                                <a href="{{ route('plans.index') }}" wire:navigate class="block px-4 py-2 text-sm hover:bg-navy-50 lg:hidden">Langganan</a>
                                <a href="{{ route('orders.index') }}" wire:navigate class="block px-4 py-2 text-sm hover:bg-navy-50">Pesanan saya</a>
                                @if ($user->isAdmin())
                                    <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm font-semibold text-brand-700 hover:bg-navy-50">Dashboard admin</a>
                                @endif
                                <form method="POST" action="{{ route('logout') }}" class="border-t border-line">
                                    @csrf
                                    <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-coral-600 hover:bg-coral-100/50">Keluar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endauth
            </header>

            <main class="rise">
                <x-flash />
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Mobile bottom navigation --}}
    <nav class="glass fixed inset-x-3 bottom-3 z-30 flex justify-around rounded-2xl px-1 py-1.5 lg:hidden" aria-label="Navigasi utama (seluler)">
        @foreach (array_slice($navigation, 0, 5) as $item)
            @php $active = request()->routeIs($item['active']); @endphp
            <a href="{{ route($item['route']) }}" wire:navigate @if ($active) aria-current="page" @endif
               @class(['flex flex-1 flex-col items-center gap-0.5 rounded-xl py-1.5 text-[11px] font-semibold transition', 'bg-brand-50 text-brand-700' => $active, 'text-muted' => ! $active])>
                <x-icon :name="$item['icon']" class="size-5" />
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>


    @stack('scripts')
</body>
</html>
