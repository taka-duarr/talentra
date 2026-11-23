<div>
    <h1 class="text-2xl font-extrabold tracking-tight">Buat password baru</h1>

    <form wire:submit="resetPassword" class="mt-8 space-y-5">
        <div>
            <label for="email" class="field-label">Email</label>
            <input wire:model="email" id="email" type="email" autocomplete="email" class="field">
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password" class="field-label">Password baru</label>
            <input wire:model="password" id="password" type="password" autocomplete="new-password" class="field">
            @error('password') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password_confirmation" class="field-label">Ulangi password baru</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" class="field">
        </div>
        <button type="submit" class="btn btn-primary w-full py-3" wire:loading.attr="disabled">Simpan password</button>
    </form>
</div>
