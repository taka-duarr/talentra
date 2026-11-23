<div class="rounded-xl border border-line bg-canvas p-4 text-sm">
    <p class="font-bold">Pedoman komunitas Talentra</p>
    <ul class="mt-2 list-disc space-y-1 pl-5 text-muted">
        <li>Saling menghormati. Tidak ada hinaan, SARA, atau pelecehan.</li>
        <li>Tidak membagikan soal ujian resmi yang bersifat rahasia atau materi berhak cipta.</li>
        <li>Tidak ada promosi, spam, judi, atau tautan berbahaya.</li>
        <li>Jangan membagikan data pribadi (nomor HP, alamat, NIK).</li>
        <li>Laporkan tulisan yang melanggar. Admin bisa menyembunyikan tulisan dan membatasi akun.</li>
    </ul>
    <label class="mt-3 flex items-center gap-2 font-semibold">
        <input type="checkbox" wire:model="acceptGuidelines" class="size-4 rounded border-line text-brand-600">
        Saya sudah membaca dan menyetujui pedoman ini
    </label>
    @error('guidelines') <p class="field-error">{{ $message }}</p> @enderror
</div>
