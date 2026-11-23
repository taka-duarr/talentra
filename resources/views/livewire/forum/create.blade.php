<div class="mx-auto max-w-2xl">
    <a href="{{ route('forum.index', ['ruang' => $room]) }}" wire:navigate class="text-sm font-semibold text-muted hover:text-ink">← Forum</a>
    <h1 class="mt-3 mb-6 text-2xl font-extrabold tracking-tight">Mulai diskusi</h1>

    <form wire:submit="save" class="card space-y-5 p-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="room" class="field-label">Ruang</label>
                <select id="room" wire:model="room" class="field">
                    <option value="umum">Forum umum</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->slug }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="tag" class="field-label">Label</label>
                <select id="tag" wire:model="tag" class="field">
                    @foreach ($tags as $item) <option value="{{ $item->value }}">{{ $item->label() }}</option> @endforeach
                </select>
            </div>
        </div>
        <div>
            <label for="title" class="field-label">Judul</label>
            <input id="title" type="text" wire:model="title" maxlength="150" class="field" placeholder="Contoh: Cara cepat mengerjakan soal deret bertingkat?">
            @error('title') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="body" class="field-label">Isi</label>
            <textarea id="body" wire:model="body" rows="8" maxlength="5000" class="field" placeholder="Jelaskan pertanyaan atau idemu. Sertakan soal atau langkah yang sudah kamu coba."></textarea>
            @error('body') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        @if ($needsGuidelines)
            <x-forum.guidelines />
        @endif

        <div class="flex justify-end">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Kirim diskusi</button>
        </div>
    </form>
</div>
