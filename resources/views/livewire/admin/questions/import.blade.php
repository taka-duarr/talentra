<div>
    <a href="{{ route('admin.questions.index') }}" wire:navigate class="text-sm font-semibold text-muted hover:text-ink">← Bank soal</a>
    <div class="mt-3">
        <x-page-header title="Import soal dari Excel" description="Isi template, unggah, periksa pratinjau, lalu impor. Baris yang bermasalah dilewati dan tidak ikut tersimpan." />
    </div>

    @if ($importedCount !== null)
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-brand-100 bg-brand-50 px-5 py-4">
            <p class="font-semibold text-brand-700">{{ $importedCount }} soal masuk ke bank soal.</p>
            <a href="{{ route('admin.questions.index', ['status' => $status]) }}" wire:navigate class="btn btn-primary btn-sm">Lihat soal</a>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[320px_1fr]">
        <aside class="space-y-4">
            <section class="card p-5">
                <h2 class="font-bold">1. Unduh template</h2>
                <p class="mt-1 text-sm text-muted">Berisi contoh kelima tipe soal, petunjuk pengisian, dan daftar nama kategori serta mata uji yang valid.</p>
                <a href="{{ route('admin.questions.template') }}" class="btn btn-outline mt-4 w-full">Unduh template .xlsx</a>
            </section>

            <form wire:submit="analyze" class="card p-5">
                <h2 class="font-bold">2. Unggah file</h2>
                <p class="mt-1 text-sm text-muted">Format .xlsx atau .csv, maksimal 5 MB dan {{ \App\Services\Questions\QuestionImporter::MAX_ROWS }} soal.</p>
                <input type="file" wire:model="file" accept=".xlsx,.csv" class="mt-4 block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-navy-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-navy-600" aria-label="File soal">
                @error('file') <p class="field-error">{{ $message }}</p> @enderror
                <button type="submit" class="btn btn-navy mt-4 w-full" wire:loading.attr="disabled" wire:target="file,analyze">
                    <span wire:loading.remove wire:target="analyze">Periksa file</span>
                    <span wire:loading wire:target="analyze">Memeriksa…</span>
                </button>
                <p wire:loading wire:target="file" class="mt-2 text-xs text-subtle">Mengunggah…</p>
            </form>

            <section class="card p-5 text-sm text-muted">
                <h2 class="font-bold text-ink">Format kunci</h2>
                <dl class="mt-2 space-y-1.5">
                    <div><dt class="inline font-semibold text-ink">pg</dt> <dd class="inline">satu huruf: <code>B</code></dd></div>
                    <div><dt class="inline font-semibold text-ink">pgk</dt> <dd class="inline">huruf yang benar: <code>A,C</code></dd></div>
                    <div><dt class="inline font-semibold text-ink">bs</dt> <dd class="inline">B/S per pernyataan: <code>B,S,B</code></dd></div>
                    <div><dt class="inline font-semibold text-ink">isian</dt> <dd class="inline">jawaban, alternatif pakai <code>|</code></dd></div>
                    <div><dt class="inline font-semibold text-ink">tkp</dt> <dd class="inline">bobot per opsi: <code>5,3,2,4,1</code></dd></div>
                </dl>
            </section>
        </aside>

        <section>
            @if ($headerError)
                <div role="alert" class="card border-coral-100 bg-coral-100/50 p-5 text-sm font-semibold text-coral-600">{{ $headerError }}</div>
            @elseif ($totalRows > 0)
                <div class="card mb-4 flex flex-wrap items-center gap-4 p-5">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm text-subtle">{{ $originalName }}</p>
                        <p class="mt-1 text-sm"><strong class="text-brand-700">{{ $validCount }} siap diimpor</strong> · <strong @class(['text-coral-600' => $invalidCount > 0])>{{ $invalidCount }} bermasalah</strong></p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <label for="status" class="text-sm font-semibold">Simpan sebagai</label>
                        <select id="status" wire:model="status" class="field w-auto py-2">
                            @foreach ($statuses as $item)
                                <option value="{{ $item->value }}">{{ $item->label() }}</option>
                            @endforeach
                        </select>
                        <button type="button" wire:click="cancel" class="btn btn-ghost">Batal</button>
                        <button type="button" wire:click="import" wire:confirm="Impor {{ $validCount }} soal ke bank soal?" class="btn btn-primary" @disabled($validCount === 0) wire:loading.attr="disabled" wire:target="import">Impor {{ $validCount }} soal</button>
                    </div>
                </div>

                @if ($invalidCount > 0)
                    <label class="mb-3 flex items-center gap-2 text-sm"><input type="checkbox" wire:model.live="onlyProblems" class="size-4 rounded border-line text-brand-600"> Tampilkan hanya baris bermasalah</label>
                @endif

                <div class="card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead><tr><th class="w-14">Baris</th><th>Soal</th><th>Mata uji</th><th>Tipe</th><th>Kunci</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr wire:key="row-{{ $row['row'] }}" @class(['bg-coral-100/30' => ! $row['valid']])>
                                        <td class="font-mono text-xs text-subtle">{{ $row['row'] }}</td>
                                        <td class="max-w-sm">
                                            <p class="line-clamp-2 text-sm">{{ $row['summary']['body'] ?: '—' }}</p>
                                            @foreach ($row['errors'] as $error)
                                                <p class="mt-1 text-xs font-semibold text-coral-600">✗ {{ $error }}</p>
                                            @endforeach
                                            @foreach ($row['notes'] as $note)
                                                <p class="mt-1 text-xs text-sun-600">• {{ $note }}</p>
                                            @endforeach
                                        </td>
                                        <td class="text-xs text-muted">{{ $row['summary']['subject'] }}</td>
                                        <td class="text-xs">{{ $row['summary']['type'] }}</td>
                                        <td class="font-mono text-xs">{{ \Illuminate\Support\Str::limit($row['summary']['answer'], 20) }}</td>
                                        <td>
                                            @if ($row['valid'])
                                                <span class="badge bg-brand-50 text-brand-700">Siap</span>
                                            @else
                                                <span class="badge bg-coral-100 text-coral-600">Dilewati</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($totalRows > count($preview))
                    <p class="mt-2 text-xs text-subtle">Pratinjau menampilkan {{ count($preview) }} baris pertama dari {{ $totalRows }}. Semua baris tetap diperiksa saat impor.</p>
                @endif
            @else
                <div class="card grid min-h-72 place-items-center p-10 text-center">
                    <div>
                        <p class="font-semibold">Belum ada file yang diperiksa</p>
                        <p class="mt-1 text-sm text-muted">Unduh template, isi soalnya, lalu unggah di panel kiri.</p>
                    </div>
                </div>
            @endif
        </section>
    </div>
</div>
