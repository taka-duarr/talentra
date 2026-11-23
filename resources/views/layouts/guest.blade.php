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
    <div class="grid min-h-screen lg:grid-cols-2">
        <section class="hidden flex-col justify-between bg-navy-600 p-12 text-white lg:flex">
            <x-brand class="text-white [&>span]:from-brand-500 [&>span]:to-brand-700" />
            <div>
                <p class="text-sm font-bold tracking-widest text-sun-400 uppercase">Tumbuh · Uji · Berkembang</p>
                <h2 class="mt-4 max-w-md text-4xl leading-tight font-extrabold">Latihan yang terarah dimulai dari tahu kelemahanmu.</h2>
                <p class="mt-4 max-w-md text-white/70">Kerjakan try out TKA, UTBK, CPNS, BUMN, dan Kedinasan, lalu dapatkan analisis hasil dan rekomendasi belajar.</p>
            </div>
            <p class="text-sm text-white/50">© {{ date('Y') }} Talentra</p>
        </section>

        <main class="flex items-center justify-center px-4 py-12">
            <div class="w-full max-w-md">
                <div class="mb-8 lg:hidden"><x-brand /></div>
                <x-flash />
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
