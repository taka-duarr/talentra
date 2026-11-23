@if (session('status'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" role="status"
         class="mb-6 flex items-start gap-3 rounded-xl border border-brand-100 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-700">
        <svg class="mt-0.5 size-4 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>
        <span class="flex-1">{{ session('status') }}</span>
        <button type="button" @click="show = false" class="text-brand-700/70 hover:text-brand-700" aria-label="Tutup pesan">&times;</button>
    </div>
@endif

@if (session('error'))
    <div role="alert" class="mb-6 rounded-xl border border-coral-100 bg-coral-100/60 px-4 py-3 text-sm font-medium text-coral-600">
        {{ session('error') }}
    </div>
@endif
