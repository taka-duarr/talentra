<div>
    <h1 class="text-2xl font-extrabold tracking-tight">Lupa password</h1>
    <p class="mt-1.5 text-sm text-muted">Masukkan email akunmu. Kami akan mengirim tautan untuk membuat password baru.</p>

    @if ($sent)
        <div role="status" class="mt-8 rounded-xl border border-brand-100 bg-brand-50 px-4 py-3 text-sm text-brand-700">
            Jika email tersebut terdaftar, tautan reset password sudah dikirim. Cek kotak masuk atau folder spam.
        </div>
    @else
        <form wire:submit="sendResetLink" class="mt-8 space-y-5">
            <div>
                <label for="email" class="field-label">Email</label>
                <input wire:model="email" id="email" type="email" autocomplete="email" autofocus class="field">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn btn-primary w-full py-3" wire:loading.attr="disabled">Kirim tautan reset</button>
        </form>
    @endif

    <p class="mt-6 text-sm"><a href="{{ route('login') }}" wire:navigate class="font-semibold text-brand-700 hover:underline">← Kembali ke halaman masuk</a></p>
</div>
