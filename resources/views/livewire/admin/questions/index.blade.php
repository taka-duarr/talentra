<div>
    <x-page-header title="Bank soal" description="Soal melewati alur draf → review → terbit. Hanya soal terbit yang ikut ke ujian.">
        <x-slot:actions>
            <a href="{{ route('admin.questions.import') }}" wire:navigate class="btn btn-outline">Import Excel</a>
            <a href="{{ route('admin.questions.create') }}" wire:navigate class="btn btn-primary">+ Soal baru</a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <select wire:model.live="category" class="field" aria-label="Filter kategori">
            <option value="">Semua kategori</option>
            @foreach ($categories as $item)
                <option value="{{ $item->id }}">{{ $item->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="status" class="field" aria-label="Filter status">
            <option value="">Semua status</option>
            @foreach ($statuses as $item)
                <option value="{{ $item->value }}">{{ $item->label() }}</option>
            @endforeach
        </select>
        <select wire:model.live="type" class="field" aria-label="Filter tipe soal">
            <option value="">Semua tipe</option>
            @foreach ($types as $item)
                <option value="{{ $item->value }}">{{ $item->label() }}</option>
            @endforeach
        </select>
        <input type="search" wire:model.live.debounce.400ms="search" class="field" placeholder="Cari isi soal" aria-label="Cari soal">
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead><tr><th class="w-1/2">Soal</th><th>Mata uji / topik</th><th>Tipe</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($questions as $question)
                        <tr wire:key="question-{{ $question->id }}">
                            <td>
                                <p class="line-clamp-2">{{ \Illuminate\Support\Str::limit(strip_tags($question->body), 140) }}</p>
                                <p class="mt-0.5 text-xs text-subtle">#{{ $question->id }} · kesulitan {{ $question->difficulty }} · dipakai di {{ $question->sections_count }} paket</p>
                            </td>
                            <td class="text-xs text-muted">{{ $question->subject->category->name }} › {{ $question->subject->name }}<br>{{ $question->topic?->name }}</td>
                            <td class="text-xs">{{ $question->type->label() }}</td>
                            <td>
                                <select wire:change="setStatus({{ $question->id }}, $event.target.value)" class="rounded-lg border border-line px-2 py-1 text-xs font-semibold {{ $question->status->badgeClass() }}" aria-label="Status soal {{ $question->id }}">
                                    @foreach ($statuses as $item)
                                        <option value="{{ $item->value }}" @selected($question->status === $item)>{{ $item->label() }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.questions.edit', $question) }}" wire:navigate class="btn btn-ghost btn-sm">Ubah</a>
                                <button type="button" wire:click="delete({{ $question->id }})" wire:confirm="Hapus soal #{{ $question->id }}?" class="btn btn-ghost btn-sm text-coral-600">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-10 text-center text-muted">Tidak ada soal.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $questions->links() }}</div>
</div>
