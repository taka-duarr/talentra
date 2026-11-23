<div class="mx-auto max-w-3xl">
    <a href="{{ route('tryout.index', ['kategori' => $package->category->slug]) }}" wire:navigate class="text-sm font-semibold text-muted hover:text-ink">← Try out {{ $package->category->name }}</a>

    <div class="card mt-4 p-6 sm:p-8">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-bold text-subtle">{{ $package->category->name }}</span>
            @if ($package->is_free)
                <span class="badge bg-sun-100 text-sun-600">Demo gratis</span>
            @endif
        </div>
        <h1 class="mt-2 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ $package->title }}</h1>
        <p class="mt-2 text-muted">{{ $package->description }}</p>

        <dl class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-xl bg-canvas p-4"><dt class="text-xs font-semibold text-subtle">Soal</dt><dd class="mt-1 text-xl font-extrabold">{{ $questionCount }}</dd></div>
            <div class="rounded-xl bg-canvas p-4"><dt class="text-xs font-semibold text-subtle">Durasi</dt><dd class="mt-1 text-xl font-extrabold">{{ $package->duration_minutes }} mnt</dd></div>
            <div class="rounded-xl bg-canvas p-4"><dt class="text-xs font-semibold text-subtle">Benar / salah</dt><dd class="mt-1 text-xl font-extrabold">+{{ $package->score_correct }} / {{ $package->score_wrong }}</dd></div>
            <div class="rounded-xl bg-canvas p-4"><dt class="text-xs font-semibold text-subtle">Kesempatan</dt><dd class="mt-1 text-xl font-extrabold">{{ $attemptsLeft ?? '∞' }}</dd></div>
        </dl>

        <h2 class="mt-8 font-bold">Bagian ujian</h2>
        <ul class="mt-3 divide-y divide-line rounded-xl border border-line">
            @foreach ($package->sections as $section)
                <li wire:key="section-{{ $section->id }}" class="flex items-center justify-between px-4 py-3 text-sm">
                    <span class="font-semibold">{{ $section->name }}</span>
                    <span class="text-muted">{{ $section->questions_count }} soal @if ($section->passing_score) · ambang batas {{ $section->passing_score }} @endif</span>
                </li>
            @endforeach
        </ul>

        <div class="mt-6 rounded-xl bg-navy-50/60 p-4 text-sm text-muted">
            <p class="font-semibold text-ink">Sebelum mulai</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>Timer berjalan di server dan tidak berhenti walaupun halaman ditutup.</li>
                <li>Jawaban tersimpan otomatis setiap kamu memilih jawaban.</li>
                <li>Kamu bisa menandai soal sebagai ragu-ragu dan kembali sebelum menyelesaikan ujian.</li>
                @if ($package->counts_for_ranking)
                    <li>Hanya percobaan pertama yang dihitung untuk ranking.</li>
                @endif
            </ul>
        </div>

        <div class="mt-6">
            @if ($running)
                <a href="{{ route('exam.show', $running) }}" class="btn btn-navy w-full py-3 sm:w-auto">Lanjutkan ujian (sisa {{ ceil($running->remainingSeconds() / 60) }} menit)</a>
            @elseif (! $unlocked)
                <a href="{{ route('plans.index') }}" wire:navigate class="btn btn-primary w-full py-3 sm:w-auto">Buka dengan langganan</a>
            @elseif ($attemptsLeft === 0)
                <p class="text-sm font-semibold text-coral-600">Kesempatan mengerjakan paket ini sudah habis.</p>
            @else
                <form method="POST" action="{{ route('exam.start', $package) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary w-full py-3 sm:w-auto">{{ $attempts->isEmpty() ? 'Mulai try out' : 'Kerjakan lagi' }}</button>
                </form>
            @endif
        </div>
    </div>

    @if ($attempts->where('status.value', 'submitted')->isNotEmpty())
        <section class="card mt-6 overflow-hidden">
            <h2 class="border-b border-line px-5 py-4 font-bold">Riwayat percobaanmu</h2>
            <ul class="divide-y divide-line">
                @foreach ($attempts->where('status.value', 'submitted') as $attempt)
                    <li wire:key="attempt-{{ $attempt->id }}" class="flex items-center justify-between px-5 py-3 text-sm">
                        <span class="text-muted">{{ $attempt->submitted_at->translatedFormat('d M Y, H:i') }} @if ($attempt->is_first_attempt) <span class="badge bg-navy-50 text-muted">Percobaan pertama</span> @endif</span>
                        <span class="flex items-center gap-4">
                            <strong>{{ $attempt->percentage() }}%</strong>
                            <a href="{{ route('results.show', $attempt) }}" wire:navigate class="font-semibold text-brand-700 hover:underline">Hasil</a>
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
