@props(['href' => null])

<a href="{{ $href ?? route('home') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 text-lg font-extrabold tracking-tight text-navy-600']) }}>
    <span class="grid size-8 place-items-center rounded-lg bg-gradient-to-br from-brand-500 to-navy-600 text-white">
        <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h11"/><path d="M9.5 6v13"/><path d="m14 15 5-5"/><path d="M15.5 10H19v3.5"/></svg>
    </span>
    Talentra
</a>
