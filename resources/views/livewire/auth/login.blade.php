<div class="auth-form">
    <p class="auth-step">01 · Masuk</p>
    <h1 id="login-heading" class="auth-title">Masuk ke akunmu</h1>
    <p class="mt-2 text-sm text-muted">Belum punya akun?
        <a href="{{ route('register') }}" @click.prevent="$dispatch('auth-switch', 'register')" class="auth-link">Daftar gratis</a>
    </p>

    <form wire:submit="login" class="mt-9 space-y-6" novalidate>
        <div>
            <label for="login-email" class="field-line-label">Email</label>
            <input wire:model="email" id="login-email" type="email" autocomplete="email" data-autofocus
                   @class(['field-line', 'is-invalid' => $errors->has('email')]) placeholder="nama@email.com"
                   @error('email') aria-invalid="true" aria-describedby="login-email-error" @enderror>
            @error('email') <p id="login-email-error" class="field-error">{{ $message }}</p> @enderror
        </div>

        <div x-data="{ reveal: false }">
            <div class="flex items-center justify-between">
                <label for="login-password" class="field-line-label">Password</label>
                <a href="{{ route('password.request') }}" wire:navigate class="auth-link text-xs">Lupa password?</a>
            </div>
            <div class="relative">
                <input wire:model="password" id="login-password" :type="reveal ? 'text' : 'password'" type="password" autocomplete="current-password"
                       @class(['field-line pr-10', 'is-invalid' => $errors->has('password')]) placeholder="••••••••"
                       @error('password') aria-invalid="true" aria-describedby="login-password-error" @enderror>
                <button type="button" class="field-line-toggle" @click="reveal = ! reveal" :aria-pressed="reveal" :aria-label="reveal ? 'Sembunyikan password' : 'Tampilkan password'">
                    <svg x-show="! reveal" class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg x-show="reveal" x-cloak class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 5.1A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3 3.9M6.6 6.6C3.8 8.4 2 12 2 12s3.6 7 10 7a10 10 0 0 0 5.4-1.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                </button>
            </div>
            @error('password') <p id="login-password-error" class="field-error">{{ $message }}</p> @enderror
        </div>

        <label class="flex w-fit cursor-pointer items-center gap-2.5 text-sm text-muted">
            <input wire:model="remember" type="checkbox" class="auth-check">
            Ingat saya di perangkat ini
        </label>

        <button type="submit" class="btn-auth" wire:loading.attr="disabled" wire:target="login">
            <span wire:loading.remove wire:target="login">Masuk</span>
            <span wire:loading wire:target="login">Memproses…</span>
            <svg wire:loading.remove wire:target="login" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
        </button>
    </form>
</div>
