@props(['value', 'display' => null])

<div x-data="{ copied: false, copy() { navigator.clipboard?.writeText(@js($value)).then(() => { this.copied = true; setTimeout(() => this.copied = false, 1600); }); } }"
     class="mt-1 flex flex-wrap items-center gap-3">
    <span {{ $attributes->merge(['class' => 'font-extrabold text-ink break-all']) }}>{{ $display ?? $value }}</span>
    <button type="button" @click="copy()" class="inline-flex items-center gap-1 rounded-lg bg-brand-50 px-2.5 py-1 text-xs font-bold text-brand-700 transition hover:bg-brand-100" :aria-label="copied ? 'Tersalin' : 'Salin'">
        <span x-show="! copied">Salin</span>
        <span x-show="copied" x-cloak>Tersalin ✓</span>
    </button>
</div>
