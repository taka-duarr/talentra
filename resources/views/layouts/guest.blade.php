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
<body class="auth-body min-h-screen font-sans">
    <div class="relative flex min-h-screen flex-col px-4 py-5 sm:px-6 lg:px-10">
        <header class="flex items-center justify-between">
            <x-brand class="text-white" />
            <a href="{{ route('login') }}" class="text-sm font-semibold text-white/60 transition hover:text-white">← Kembali ke halaman masuk</a>
        </header>

        <main class="flex flex-1 items-center justify-center py-8">
            <div class="w-full max-w-md rounded-[28px] bg-[#fbf8f1] p-7 shadow-2xl shadow-black/40 sm:p-10">
                <x-flash />
                {{ $slot }}
            </div>
        </main>

        <p class="text-center text-xs text-white/40">© {{ date('Y') }} Talentra · Tumbuh, uji, berkembang.</p>
    </div>
</body>
</html>
