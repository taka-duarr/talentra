<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @fonts
    @vite(['resources/js/exam.js'])
    <x-inertia::head>
        <title>Ujian · Talentra</title>
    </x-inertia::head>
</head>
<body class="min-h-screen bg-canvas font-sans">
    <x-inertia::app />
</body>
</html>
