<div class="auth-form">
    <p class="auth-step">02 · Daftar</p>
    <h1 id="register-heading" class="auth-title">Buat akun gratis</h1>
    <p class="mt-2 text-sm text-muted">Sudah punya akun?
        <a href="{{ route('login') }}" @click.prevent="$dispatch('auth-switch', 'login')" class="auth-link">Masuk</a>
    </p>

    <form wire:submit="register" class="mt-7 space-y-5" novalidate>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="register-name" class="field-line-label">Nama lengkap</label>
                <input wire:model="name" id="register-name" type="text" autocomplete="name" data-autofocus
                       @class(['field-line', 'is-invalid' => $errors->has('name')]) placeholder="Nama kamu"
                       @error('name') aria-invalid="true" aria-describedby="register-name-error" @enderror>
                @error('name') <p id="register-name-error" class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="register-email" class="field-line-label">Email</label>
                <input wire:model="email" id="register-email" type="email" autocomplete="email"
                       @class(['field-line', 'is-invalid' => $errors->has('email')]) placeholder="nama@email.com"
                       @error('email') aria-invalid="true" aria-describedby="register-email-error" @enderror>
                @error('email') <p id="register-email-error" class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <fieldset>
            <legend class="field-line-label">Ujian yang sedang kamu persiapkan</legend>
            <div class="mt-1 flex flex-wrap gap-2">
                @foreach ($this->categories as $category)
                    <label wire:key="target-{{ $category->id }}" class="auth-chip">
                        <input type="radio" wire:model="target_category_id" value="{{ $category->id }}" class="sr-only">
                        {{ $category->name }}
                    </label>
                @endforeach
            </div>
            @error('target_category_id') <p class="field-error">{{ $message }}</p> @enderror
        </fieldset>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="register-password" class="field-line-label">Password</label>
                <input wire:model="password" id="register-password" type="password" autocomplete="new-password"
                       @class(['field-line', 'is-invalid' => $errors->has('password')]) placeholder="Minimal 8 karakter"
                       @error('password') aria-invalid="true" aria-describedby="register-password-error" @enderror>
                @error('password') <p id="register-password-error" class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="register-password-confirmation" class="field-line-label">Ulangi password</label>
                <input wire:model="password_confirmation" id="register-password-confirmation" type="password" autocomplete="new-password" class="field-line" placeholder="Ketik ulang">
            </div>
        </div>

        <div>
            <label class="flex cursor-pointer items-start gap-2.5 text-xs leading-relaxed text-muted">
                <input wire:model="accepted_terms" type="checkbox" class="auth-check mt-0.5">
                <span>Saya menyetujui syarat & ketentuan serta kebijakan privasi Talentra. Pengguna di bawah 17 tahun mendaftar dengan persetujuan orang tua atau wali.</span>
            </label>
            @error('accepted_terms') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn-auth" wire:loading.attr="disabled" wire:target="register">
            <span wire:loading.remove wire:target="register">Daftar & mulai belajar</span>
            <span wire:loading wire:target="register">Membuat akun…</span>
            <svg wire:loading.remove wire:target="register" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
        </button>
    </form>
</div>
