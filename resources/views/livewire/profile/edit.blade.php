<div class="mx-auto max-w-2xl">
    <x-page-header title="Profil & pengaturan" />

    <form wire:submit="saveProfile" class="card space-y-5 p-6">
        <h2 class="font-bold">Profil</h2>
        <div>
            <label for="name" class="field-label">Nama lengkap</label>
            <input id="name" type="text" wire:model="name" class="field" autocomplete="name">
            @error('name') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="display_name" class="field-label">Nama tampilan</label>
            <input id="display_name" type="text" wire:model="display_name" class="field" placeholder="Dipakai di ranking dan forum">
            <p class="mt-1 text-xs text-subtle">Kosongkan untuk memakai nama lengkap.</p>
            @error('display_name') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="target" class="field-label">Ujian yang sedang dipersiapkan</label>
            <select id="target" wire:model="target_category_id" class="field">
                <option value="">Belum memilih</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <label class="flex items-start gap-3 text-sm">
            <input type="checkbox" wire:model="hide_from_ranking" class="mt-0.5 size-4 rounded border-line text-brand-600 focus:ring-brand-500">
            <span><strong>Sembunyikan saya dari ranking publik</strong><br><span class="text-muted">Nilaimu tetap dihitung, tetapi namamu tidak ditampilkan ke peserta lain.</span></span>
        </label>
        <label class="flex items-start gap-3 text-sm">
            <input type="checkbox" wire:model="email_notifications" class="mt-0.5 size-4 rounded border-line text-brand-600 focus:ring-brand-500">
            <span><strong>Kirim notifikasi lewat email</strong><br><span class="text-muted">Balasan forum dan pengingat langganan. Email pembayaran tetap selalu dikirim.</span></span>
        </label>
        <button type="submit" class="btn btn-primary">Simpan profil</button>
    </form>

    <form wire:submit="updatePassword" class="card mt-6 space-y-5 p-6">
        <h2 class="font-bold">Ganti password</h2>
        <div>
            <label for="current_password" class="field-label">Password saat ini</label>
            <input id="current_password" type="password" wire:model="current_password" class="field" autocomplete="current-password">
            @error('current_password') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="new_password" class="field-label">Password baru</label>
                <input id="new_password" type="password" wire:model="password" class="field" autocomplete="new-password">
                @error('password') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="new_password_confirmation" class="field-label">Ulangi password baru</label>
                <input id="new_password_confirmation" type="password" wire:model="password_confirmation" class="field" autocomplete="new-password">
            </div>
        </div>
        <button type="submit" class="btn btn-navy">Ubah password</button>
    </form>
</div>
