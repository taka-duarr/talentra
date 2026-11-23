<div>
    <h1 class="text-2xl font-extrabold tracking-tight">Masuk ke Talentra</h1>
    <p class="mt-1.5 text-sm text-muted">Belum punya akun? <a href="{{ route('register') }}" wire:navigate class="font-semibold text-brand-700 hover:underline">Daftar gratis</a></p>

    <form wire:submit="login" class="mt-8 space-y-5">
        <div>
            <label for="email" class="field-label">Email</label>
            <input wire:model="email" id="email" type="email" autocomplete="email" autofocus class="field" placeholder="nama@email.com">
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="field-label">Password</label>
                <a href="{{ route('password.request') }}" wire:navigate class="mb-1.5 text-xs font-semibold text-brand-700 hover:underline">Lupa password?</a>
            </div>
            <input wire:model="password" id="password" type="password" autocomplete="current-password" class="field">
            @error('password') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-muted">
            <input wire:model="remember" type="checkbox" class="size-4 rounded border-line text-brand-600 focus:ring-brand-500">
            Ingat saya
        </label>

        <button type="submit" class="btn btn-primary w-full py-3" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="login">Masuk</span>
            <span wire:loading wire:target="login">Memproses…</span>
        </button>
    </form>
</div>
