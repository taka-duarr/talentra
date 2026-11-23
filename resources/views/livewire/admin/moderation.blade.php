<div>
    <x-page-header title="Moderasi forum" description="Tinjau laporan dari pengguna. Menyembunyikan tulisan tidak menghapusnya, jadi bisa dikembalikan." />

    <div class="mb-4 flex gap-2" role="tablist">
        <button type="button" wire:click="$set('tab', 'laporan')" role="tab" aria-selected="{{ $tab === 'laporan' ? 'true' : 'false' }}" @class(['btn btn-sm', 'btn-navy' => $tab === 'laporan', 'btn-outline' => $tab !== 'laporan'])>Laporan terbuka ({{ $reportGroups->count() }})</button>
        <button type="button" wire:click="$set('tab', 'tersembunyi')" role="tab" aria-selected="{{ $tab === 'tersembunyi' ? 'true' : 'false' }}" @class(['btn btn-sm', 'btn-navy' => $tab === 'tersembunyi', 'btn-outline' => $tab !== 'tersembunyi'])>Disembunyikan</button>
    </div>

    @if ($tab === 'laporan')
        <div class="space-y-4">
            @forelse ($reportGroups as $key => $reports)
                @php
                    $item = $reports->first()->reportable;
                    $type = $item instanceof \App\Models\ForumThread ? 'thread' : 'reply';
                    $thread = $type === 'thread' ? $item : $item->thread;
                @endphp
                <article wire:key="report-{{ $key }}" class="card p-5">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-subtle">
                        <span class="badge bg-coral-100 text-coral-600">{{ $reports->count() }} laporan</span>
                        <span>{{ $type === 'thread' ? 'Diskusi' : 'Balasan' }} oleh <strong class="text-ink">{{ $item->author->name }}</strong> ({{ $item->author->email }})</span>
                        @if ($item->isHidden()) <span class="badge bg-navy-50 text-muted">Sudah disembunyikan</span> @endif
                    </div>
                    @if ($type === 'thread')
                        <h2 class="mt-2 font-bold">{{ $item->title }}</h2>
                    @else
                        <p class="mt-2 text-xs text-subtle">Di diskusi: <a href="{{ route('forum.thread', $thread) }}" target="_blank" class="font-semibold text-brand-700">{{ $thread->title }}</a></p>
                    @endif
                    <p class="mt-2 rounded-xl bg-canvas p-3 text-sm whitespace-pre-line">{{ \Illuminate\Support\Str::limit($item->body, 600) }}</p>
                    <ul class="mt-3 space-y-1 text-xs text-muted">
                        @foreach ($reports as $report)
                            <li>“{{ $report->reason }}” — {{ $report->reporter->name }}, {{ $report->created_at->diffForHumans() }}</li>
                        @endforeach
                    </ul>
                    <div class="mt-4 flex flex-wrap gap-2 border-t border-line pt-4">
                        <button type="button" wire:click="hide('{{ $type }}', {{ $item->id }})" class="btn btn-danger btn-sm">Sembunyikan</button>
                        <button type="button" wire:click="dismiss('{{ $type }}', {{ $item->id }})" class="btn btn-outline btn-sm">Abaikan laporan</button>
                        <button type="button" wire:click="banAuthor('{{ $type }}', {{ $item->id }}, 7)" wire:confirm="Larang {{ $item->author->name }} menulis di forum selama 7 hari?" class="btn btn-ghost btn-sm">Larang penulis 7 hari</button>
                        <a href="{{ route('forum.thread', $thread) }}" target="_blank" class="btn btn-ghost btn-sm">Buka diskusi</a>
                    </div>
                </article>
            @empty
                <p class="card p-10 text-center text-muted">Tidak ada laporan terbuka. 🎉</p>
            @endforelse
        </div>
    @else
        <div class="card overflow-hidden">
            <ul class="divide-y divide-line">
                @forelse ($hidden as $item)
                    @php $type = $item instanceof \App\Models\ForumThread ? 'thread' : 'reply'; @endphp
                    <li wire:key="hidden-{{ $type }}-{{ $item->id }}" class="flex items-start gap-4 px-5 py-3 text-sm">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold">{{ $type === 'thread' ? $item->title : 'Balasan di: '.$item->thread->title }}</p>
                            <p class="truncate text-muted">{{ $item->body }}</p>
                            <p class="text-xs text-subtle">{{ $item->author->name }} · disembunyikan {{ $item->hidden_at->diffForHumans() }}</p>
                        </div>
                        <button type="button" wire:click="restore('{{ $type }}', {{ $item->id }})" class="btn btn-outline btn-sm">Tampilkan lagi</button>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-muted">Tidak ada tulisan yang disembunyikan.</li>
                @endforelse
            </ul>
        </div>
    @endif
</div>
