<div>
    <h1 class="text-2xl font-extrabold tracking-tight">Buat akun gratis</h1>
    <p class="mt-1.5 text-sm text-muted">Sudah punya akun? <a href="{{ route('login') }}" wire:navigate class="font-semibold text-brand-700 hover:underline">Masuk</a></p>

    <form wire:submit="register" class="mt-8 space-y-5">
        <div>
            <label for="name" class="field-label">Nama lengkap</label>
            <input wire:model="name" id="name" type="text" autocomplete="name" autofocus class="field">
            @error('name') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="field-label">Email</label>
            <input wire:model="email" id="email" type="email" autocomplete="email" class="field" placeholder="nama@email.com">
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <fieldset>
            <legend class="field-label">Ujian yang sedang kamu persiapkan</legend>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                @foreach ($this->categories as $category)
                    <label wire:key="target-{{ $category->id }}"
                           class="flex cursor-pointer items-center justify-center rounded-xl border px-3 py-2.5 text-center text-sm font-semibold transition has-checked:border-brand-500 has-checked:bg-brand-50 has-checked:text-brand-700 border-line text-muted hover:border-navy-100">
                        <input type="radio" wire:model="target_category_id" value="{{ $category->id }}" class="sr-only">
                        {{ $category->name }}
                    </label>
                @endforeach
            </div>
            @error('target_category_id') <p class="field-error">{{ $message }}</p> @enderror
        </fieldset>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="password" class="field-label">Password</label>
                <input wire:model="password" id="password" type="password" autocomplete="new-password" class="field">
                @error('password') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="field-label">Ulangi password</label>
                <input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" class="field">
            </div>
        </div>

        <div>
            <label class="flex items-start gap-2 text-sm text-muted">
                <input wire:model="accepted_terms" type="checkbox" class="mt-0.5 size-4 rounded border-line text-brand-600 focus:ring-brand-500">
                <span>Saya menyetujui syarat & ketentuan serta kebijakan privasi Talentra. Pengguna di bawah 17 tahun mendaftar dengan persetujuan orang tua atau wali.</span>
            </label>
            @error('accepted_terms') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn btn-primary w-full py-3" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="register">Daftar & mulai belajar</span>
            <span wire:loading wire:target="register">Membuat akun…</span>
        </button>
    </form>
</div>
