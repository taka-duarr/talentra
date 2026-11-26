@php
    use App\Support\Html;
    use Illuminate\Support\Js;
    use Illuminate\Support\Str;
@endphp
<div
    x-data="{ activeMaterial: null, activeQuestion: null, activePackage: null }"
    @keydown.escape.window="activeMaterial = null; activeQuestion = null; activePackage = null"
>
    {{-- Header & Course Switcher --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.courses.index') }}" wire:navigate class="text-sm font-semibold text-muted hover:text-ink">← Semua kursus</a>

        <div class="flex items-center gap-2">
            <label for="course-switcher" class="text-xs font-semibold text-subtle">Pindah kursus:</label>
            <select
                id="course-switcher"
                wire:change="switchCourse($event.target.value)"
                class="field w-auto py-1.5 text-sm font-semibold"
            >
                @foreach ($allCourses as $c)
                    <option value="{{ $c->slug }}" @selected($c->id === $category->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-start gap-3">
            <span class="mt-1.5 size-3.5 flex-none rounded-full" style="background: {{ $category->color }}"></span>
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-extrabold tracking-tight text-ink sm:text-3xl">{{ $category->name }}</h1>
                    <span class="badge px-2.5 py-1 text-xs {{ $category->is_active ? 'bg-brand-50 text-brand-700' : 'bg-navy-50 text-muted' }}">
                        {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
                @if ($category->description)
                    <p class="mt-1 text-sm text-muted">{{ $category->description }}</p>
                @endif
            </div>
        </div>

        <a href="{{ route('learn.category', $category) }}" target="_blank" class="btn btn-outline btn-sm text-sm">
            Lihat tampilan siswa
        </a>
    </div>

    {{-- Course Tabs --}}
    <div class="mt-6 mb-6 flex gap-2 overflow-x-auto border-b border-line pb-3" role="tablist" aria-label="Menu kursus">
        @php
            $tabs = [
                ['kurikulum', 'Kurikulum & Materi', $category->subjects->count().' modul · '.$materialsCount.' materi'],
                ['soal', 'Bank Soal', $totalQuestionsCount.' soal'],
                ['tryout', 'Tes Try Out (TO)', $packages->count().' paket'],
                ['pengaturan', 'Pengaturan & Topik', null],
            ];
        @endphp
        @foreach ($tabs as [$key, $label, $countLabel])
            <button
                type="button"
                role="tab"
                aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                wire:click="setTab('{{ $key }}')"
                @class([
                    'flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold whitespace-nowrap transition',
                    'bg-navy-600 text-white shadow-xs' => $tab === $key,
                    'bg-white text-muted hover:bg-navy-50 hover:text-ink border border-line' => $tab !== $key,
                ])
            >
                <span>{{ $label }}</span>
                @if ($countLabel)
                    <span @class(['rounded-full px-2 py-0.5 text-xs font-semibold', 'bg-white/20 text-white' => $tab === $key, 'bg-navy-50 text-muted' => $tab !== $key])>
                        {{ $countLabel }}
                    </span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- TAB 1: KURIKULUM & MATERI --}}
    @if ($tab === 'kurikulum')
        <div class="space-y-5">
            <form wire:submit="addSubject" class="card flex flex-wrap items-center gap-3 p-4 sm:p-5">
                <div class="min-w-60 flex-1">
                    <label for="new-module" class="sr-only">Nama modul baru</label>
                    <input id="new-module" type="text" wire:model="newSubject" class="field text-sm" placeholder="Tambah modul / mata uji baru di {{ $category->name }} (contoh: Penalaran Umum)">
                </div>
                <button type="submit" class="btn btn-primary text-sm">+ Tambah modul</button>
                @error('newSubject') <p class="field-error w-full">{{ $message }}</p> @enderror
            </form>

            @forelse ($category->subjects as $subject)
                <section wire:key="mod-{{ $subject->id }}" class="card overflow-hidden" x-data="{ open: true }">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line bg-navy-50/40 px-5 py-4">
                        <div class="flex min-w-0 flex-1 items-center gap-2.5">
                            <button type="button" @click="open = !open" class="text-subtle hover:text-ink" :aria-expanded="open" aria-label="Buka modul">
                                <svg class="size-4 transition" :class="open && 'rotate-90'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m9 6 6 6-6 6"/></svg>
                            </button>
                            <span class="badge bg-brand-50 px-2.5 py-1 text-xs font-bold text-brand-700">Modul {{ $loop->iteration }}</span>
                            <input
                                type="text"
                                value="{{ $subject->name }}"
                                wire:change="renameSubject({{ $subject->id }}, $event.target.value)"
                                class="min-w-0 flex-1 rounded-lg border border-transparent bg-transparent px-2 py-1 text-base font-extrabold text-ink hover:border-line focus:border-brand-500 focus:bg-white focus:outline-none"
                                aria-label="Nama modul"
                            >
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-muted">{{ $subject->chapters->count() }} bab · {{ $subject->chapters->sum(fn ($c) => $c->materials->count()) }} materi</span>
                            <button type="button" wire:click="deleteSubject({{ $subject->id }})" wire:confirm="Hapus modul {{ $subject->name }}?" class="btn btn-ghost btn-sm text-sm text-coral-600">Hapus modul</button>
                        </div>
                    </div>

                    <div x-show="open" class="divide-y divide-line">
                        @forelse ($subject->chapters as $chapter)
                            <div wire:key="chap-{{ $chapter->id }}" class="p-5">
                                <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                                    <div class="flex min-w-0 flex-1 items-center gap-2">
                                        <span class="text-xs font-bold tracking-wider text-subtle uppercase">Bab {{ $loop->iteration }}</span>
                                        <input
                                            type="text"
                                            value="{{ $chapter->title }}"
                                            wire:change="renameChapter({{ $chapter->id }}, $event.target.value)"
                                            class="min-w-0 flex-1 rounded-lg border border-transparent px-2 py-1 text-[15px] font-bold text-ink hover:border-line focus:border-brand-500 focus:bg-white focus:outline-none"
                                            aria-label="Judul bab"
                                        >
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.materials.create', ['bab' => $chapter->id, 'kursus' => $category->slug]) }}" wire:navigate class="btn btn-outline btn-sm text-sm">
                                            + Materi di bab ini
                                        </a>
                                        <button type="button" wire:click="deleteChapter({{ $chapter->id }})" wire:confirm="Hapus bab {{ $chapter->title }}?" class="btn btn-ghost btn-sm text-sm text-coral-600">Hapus bab</button>
                                    </div>
                                </div>

                                @if ($chapter->materials->isNotEmpty())
                                    <div class="overflow-x-auto rounded-xl border border-line">
                                        <table class="table-base text-[15px] [&_td]:py-3.5 [&_th]:py-2.5 [&_th]:text-xs">
                                            <thead class="bg-canvas/70">
                                                <tr>
                                                    <th>Judul Materi</th>
                                                    <th>Format</th>
                                                    <th>Akses</th>
                                                    <th>Status</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($chapter->materials as $material)
                                                    @php
                                                        $materialPayload = [
                                                            'title' => $material->title,
                                                            'location' => $category->name.' › '.$subject->name.' › '.$chapter->title,
                                                            'access' => $material->is_free ? 'Gratis' : 'Langganan',
                                                            'status' => $material->published_at ? 'Terbit' : 'Draf',
                                                            'videoUrl' => $material->video_url,
                                                            'contentHtml' => Html::clean($material->content),
                                                            'editUrl' => route('admin.materials.edit', ['material' => $material, 'kursus' => $category->slug]),
                                                        ];
                                                    @endphp
                                                    <tr wire:key="cmat-{{ $material->id }}">
                                                        <td class="font-bold text-ink">{{ $material->title }}</td>
                                                        <td class="text-sm text-muted">{{ $material->kindLabel() }} · {{ $material->readingMinutes() }} mnt</td>
                                                        <td>
                                                            @if ($material->is_free)
                                                                <span class="badge bg-sun-100 px-2.5 py-1 text-xs text-sun-600">Gratis</span>
                                                            @else
                                                                <span class="badge bg-navy-50 px-2.5 py-1 text-xs text-muted">Langganan</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if ($material->published_at)
                                                                <span class="badge bg-brand-50 px-2.5 py-1 text-xs text-brand-700">Terbit</span>
                                                            @else
                                                                <span class="badge bg-navy-50 px-2.5 py-1 text-xs text-muted">Draf</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-right whitespace-nowrap">
                                                            <button type="button" x-on:click="activeMaterial = {{ Js::from($materialPayload) }}" class="btn btn-outline btn-sm text-sm">Pratinjau</button>
                                                            <a href="{{ route('admin.materials.edit', ['material' => $material, 'kursus' => $category->slug]) }}" wire:navigate class="btn btn-ghost btn-sm text-sm">Ubah</a>
                                                            <button type="button" wire:click="deleteMaterial({{ $material->id }})" wire:confirm="Hapus materi {{ $material->title }}?" class="btn btn-ghost btn-sm text-sm text-coral-600">Hapus</button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="rounded-xl border border-dashed border-line px-4 py-4 text-sm text-muted">
                                        Belum ada materi di bab ini. Klik <strong>+ Materi di bab ini</strong> untuk menambahkan bacaan atau video.
                                    </p>
                                @endif
                            </div>
                        @empty
                            <p class="px-5 py-5 text-sm text-muted">Belum ada bab di modul ini. Tambahkan bab pertama di bawah.</p>
                        @endforelse

                        <div class="flex flex-wrap items-center gap-2 bg-canvas/40 px-5 py-3.5">
                            <input
                                type="text"
                                wire:model="newChapter.{{ $subject->id }}"
                                wire:keydown.enter.prevent="addChapter({{ $subject->id }})"
                                class="field max-w-sm py-2 text-sm"
                                placeholder="Nama bab baru untuk {{ $subject->name }}"
                            >
                            <button type="button" wire:click="addChapter({{ $subject->id }})" class="btn btn-outline btn-sm text-sm">+ Tambah bab</button>
                        </div>
                    </div>
                </section>
            @empty
                <div class="card p-10 text-center text-base text-muted">
                    Kursus ini belum memiliki modul. Tambahkan modul pertama pada kolom di atas.
                </div>
            @endforelse
        </div>
    @endif

    {{-- TAB 2: BANK SOAL KURSUS --}}
    @if ($tab === 'soal')
        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-muted">Menampilkan bank soal khusus kursus <strong class="text-ink">{{ $category->name }}</strong>.</p>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.questions.import') }}" wire:navigate class="btn btn-outline btn-sm text-sm">Import Excel</a>
                    <a href="{{ route('admin.questions.create', array_filter(['kursus' => $category->slug, 'mata_uji' => $subjectFilter ?: $category->subjects->first()?->id])) }}" wire:navigate class="btn btn-primary btn-sm text-sm">
                        + Soal baru di {{ $category->name }}
                    </a>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <select wire:model.live="subjectFilter" class="field text-sm" aria-label="Filter modul">
                    <option value="">Semua modul ({{ $category->subjects->count() }})</option>
                    @foreach ($category->subjects as $sub)
                        <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="questionStatus" class="field text-sm" aria-label="Filter status soal">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $item)
                        <option value="{{ $item->value }}">{{ $item->label() }}</option>
                    @endforeach
                </select>
                <select wire:model.live="questionType" class="field text-sm" aria-label="Filter tipe soal">
                    <option value="">Semua tipe</option>
                    @foreach ($types as $item)
                        <option value="{{ $item->value }}">{{ $item->label() }}</option>
                    @endforeach
                </select>
                <input type="search" wire:model.live.debounce.400ms="questionSearch" class="field text-sm" placeholder="Cari isi soal di kursus ini" aria-label="Cari soal">
            </div>

            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="table-base text-[15px] [&_td]:py-4 [&_th]:py-3.5 [&_th]:text-[13px]">
                        <thead>
                            <tr>
                                <th class="w-5/12">Soal</th>
                                <th>Modul / Topik</th>
                                <th>Tipe</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($questions as $question)
                                @php
                                    $questionPayload = [
                                        'id' => $question->id,
                                        'location' => $category->name.' › '.$question->subject->name.($question->topic ? ' › '.$question->topic->name : ''),
                                        'type' => $question->type->label(),
                                        'bodyHtml' => Html::clean($question->body),
                                        'explanationHtml' => $question->explanation ? Html::clean($question->explanation) : null,
                                        'editUrl' => route('admin.questions.edit', ['question' => $question, 'kursus' => $category->slug]),
                                        'options' => $question->options->values()->map(fn ($opt, $idx) => [
                                            'label' => chr(65 + $idx),
                                            'body' => strip_tags($opt->body),
                                            'isCorrect' => (bool) $opt->is_correct,
                                            'weight' => $opt->weight,
                                        ])->all(),
                                    ];
                                @endphp
                                <tr wire:key="cquest-{{ $question->id }}">
                                    <td>
                                        <p class="line-clamp-2 text-[15px] font-semibold text-ink">{{ Str::limit(strip_tags($question->body), 140) }}</p>
                                        <p class="mt-1 text-sm text-muted">#{{ $question->id }} · kesulitan {{ $question->difficulty }} · dipakai di {{ $question->sections_count }} paket</p>
                                    </td>
                                    <td>
                                        <span class="text-sm font-semibold text-ink">{{ $question->subject->name }}</span>
                                        @if ($question->topic)
                                            <span class="mt-0.5 block text-sm text-muted">{{ $question->topic->name }}</span>
                                        @endif
                                    </td>
                                    <td class="text-sm font-medium whitespace-nowrap text-ink">{{ $question->type->label() }}</td>
                                    <td>
                                        <select wire:change="setQuestionStatus({{ $question->id }}, $event.target.value)" class="rounded-lg border border-line px-2.5 py-1.5 text-sm font-semibold {{ $question->status->badgeClass() }}" aria-label="Status soal {{ $question->id }}">
                                            @foreach ($statuses as $item)
                                                <option value="{{ $item->value }}" @selected($question->status === $item)>{{ $item->label() }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="text-right whitespace-nowrap">
                                        <button type="button" x-on:click="activeQuestion = {{ Js::from($questionPayload) }}" class="btn btn-outline btn-sm text-sm">Pratinjau</button>
                                        <a href="{{ route('admin.questions.edit', ['question' => $question, 'kursus' => $category->slug]) }}" wire:navigate class="btn btn-ghost btn-sm text-sm">Ubah</a>
                                        <button type="button" wire:click="deleteQuestion({{ $question->id }})" wire:confirm="Hapus soal #{{ $question->id }}?" class="btn btn-ghost btn-sm text-sm text-coral-600">Hapus</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-base text-muted">Belum ada soal pada kursus {{ $category->name }}.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div>{{ $questions->links() }}</div>
        </div>
    @endif

    {{-- TAB 3: TES TRY OUT (TO) KURSUS --}}
    @if ($tab === 'tryout')
        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-muted">Paket Tes Try Out evaluasi akhir untuk kursus <strong class="text-ink">{{ $category->name }}</strong>.</p>
                <a href="{{ route('admin.packages.create', ['kursus' => $category->slug]) }}" wire:navigate class="btn btn-primary btn-sm text-sm">
                    + Paket Try Out {{ $category->name }}
                </a>
            </div>

            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="table-base text-[15px] [&_td]:py-4 [&_th]:py-3.5 [&_th]:text-[13px]">
                        <thead>
                            <tr>
                                <th>Paket Try Out</th>
                                <th>Bagian & Soal</th>
                                <th>Durasi</th>
                                <th>Dikerjakan</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($packages as $package)
                                @php
                                    $totalQ = $package->sections->sum('questions_count');
                                    $packagePayload = [
                                        'title' => $package->title,
                                        'duration' => $package->duration_minutes.' menit',
                                        'attempts' => $package->attempts_count.'× dikerjakan',
                                        'editUrl' => route('admin.packages.edit', ['package' => $package, 'kursus' => $category->slug]),
                                        'sections' => $package->sections->map(fn ($s) => [
                                            'name' => $s->name,
                                            'questions' => $s->questions_count.' soal',
                                            'passingScore' => $s->passing_score !== null ? 'Ambang batas '.$s->passing_score : null,
                                        ])->values()->all(),
                                    ];
                                @endphp
                                <tr wire:key="cpkg-{{ $package->id }}">
                                    <td>
                                        <span class="text-base font-bold text-ink">{{ $package->title }}</span>
                                        @if ($package->is_free)
                                            <span class="badge ml-1.5 bg-sun-100 px-2.5 py-0.5 text-xs text-sun-600">Demo</span>
                                        @endif
                                        @if ($package->description)
                                            <span class="mt-0.5 block text-sm text-muted">{{ $package->description }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <button type="button" x-on:click="activePackage = {{ Js::from($packagePayload) }}" class="btn btn-outline btn-sm text-sm">
                                            Lihat bagian ({{ $package->sections->count() }} · {{ $totalQ }} soal)
                                        </button>
                                    </td>
                                    <td class="text-sm font-medium whitespace-nowrap text-ink">{{ $package->duration_minutes }} mnt</td>
                                    <td class="text-sm font-medium whitespace-nowrap text-muted">{{ $package->attempts_count }}×</td>
                                    <td>
                                        <span class="badge px-3 py-1 text-sm {{ $package->status->badgeClass() }}">{{ $package->status->label() }}</span>
                                    </td>
                                    <td class="text-right whitespace-nowrap">
                                        <a href="{{ route('admin.packages.edit', ['package' => $package, 'kursus' => $category->slug]) }}" wire:navigate class="btn btn-ghost btn-sm text-sm">Kelola</a>
                                        <button type="button" wire:click="deletePackage({{ $package->id }})" wire:confirm="Hapus paket {{ $package->title }}?" class="btn btn-ghost btn-sm text-sm text-coral-600">Hapus</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-10 text-center text-base text-muted">Belum ada paket Try Out pada kursus {{ $category->name }}.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- TAB 4: PENGATURAN KURSUS & TOPIK ANALISIS --}}
    @if ($tab === 'pengaturan')
        <div class="grid gap-6 lg:grid-cols-2">
            <form wire:submit="saveCourse" class="card h-fit space-y-4 p-5">
                <h2 class="text-lg font-extrabold text-ink">Informasi kursus</h2>

                <div>
                    <label for="c-name" class="field-label">Nama kursus</label>
                    <input id="c-name" type="text" wire:model="courseForm.name" class="field">
                    @error('courseForm.name') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="c-desc" class="field-label">Deskripsi</label>
                    <input id="c-desc" type="text" wire:model="courseForm.description" class="field">
                    @error('courseForm.description') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="c-color" class="field-label">Warna aksen</label>
                        <input id="c-color" type="color" wire:model="courseForm.color" class="h-10 w-full cursor-pointer rounded-xl border border-line">
                    </div>
                    <div>
                        <label for="c-order" class="field-label">Urutan tampil</label>
                        <input id="c-order" type="number" min="0" wire:model="courseForm.sort_order" class="field">
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm font-medium">
                    <input type="checkbox" wire:model="courseForm.is_active" class="size-4 rounded border-line text-brand-600">
                    Tampilkan kursus ke pengguna
                </label>

                <div class="pt-2">
                    <button type="submit" class="btn btn-primary">Simpan pengaturan</button>
                </div>
            </form>

            <section class="card space-y-4 p-5">
                <div>
                    <h2 class="text-lg font-extrabold text-ink">Topik analisis per modul</h2>
                    <p class="mt-0.5 text-sm text-muted">Topik dihubungkan ke bab materi agar analisis AI setelah Try Out langsung merekomendasikan bab yang tepat.</p>
                </div>

                @forelse ($category->subjects as $subject)
                    <div wire:key="topic-sub-{{ $subject->id }}" class="rounded-xl border border-line p-4">
                        <h3 class="text-sm font-bold text-ink">{{ $subject->name }}</h3>

                        <div class="mt-2.5 flex flex-wrap gap-1.5">
                            @forelse ($subject->topics as $topic)
                                <span wire:key="top-{{ $topic->id }}" class="inline-flex items-center gap-1.5 rounded-full bg-navy-50 px-3 py-1 text-xs font-semibold text-ink">
                                    {{ $topic->name }}
                                    @if ($topic->chapter)
                                        <span class="text-subtle">({{ $topic->chapter->title }})</span>
                                    @endif
                                    <button type="button" wire:click="deleteTopic({{ $topic->id }})" wire:confirm="Hapus topik {{ $topic->name }}?" class="text-subtle hover:text-coral-600">×</button>
                                </span>
                            @empty
                                <span class="text-xs text-subtle">Belum ada topik.</span>
                            @endforelse
                        </div>

                        <div class="mt-3 grid gap-2 sm:grid-cols-[1fr_auto_auto]">
                            <input type="text" wire:model="newTopic.{{ $subject->id }}" class="field py-1.5 text-sm" placeholder="Topik baru">
                            <select wire:model="newTopicChapter.{{ $subject->id }}" class="field py-1.5 text-sm" aria-label="Bab terkait">
                                <option value="">Tanpa bab</option>
                                @foreach ($subject->chapters as $chap)
                                    <option value="{{ $chap->id }}">{{ $chap->title }}</option>
                                @endforeach
                            </select>
                            <button type="button" wire:click="addTopic({{ $subject->id }})" class="btn btn-outline btn-sm text-sm">Tambah</button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-muted">Tambahkan modul terlebih dahulu pada tab Kurikulum & Materi.</p>
                @endforelse
            </section>
        </div>
    @endif

    {{-- Modal Pratinjau Materi --}}
    <template x-teleport="body">
        <div x-cloak x-show="activeMaterial !== null" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4 backdrop-blur-xs" x-on:click.self="activeMaterial = null" role="dialog" aria-modal="true">
            <div x-show="activeMaterial !== null" x-transition class="flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-line bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-line px-5 py-4">
                    <div>
                        <h2 class="text-lg font-extrabold text-ink" x-text="activeMaterial?.title"></h2>
                        <p class="mt-0.5 text-sm text-muted" x-text="activeMaterial?.location"></p>
                    </div>
                    <button type="button" x-on:click="activeMaterial = null" class="grid size-8 flex-none place-items-center rounded-lg text-lg text-subtle hover:bg-navy-50">×</button>
                </div>
                <div class="space-y-4 overflow-y-auto px-5 py-4">
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="badge bg-navy-50 px-2.5 py-1 text-ink" x-text="'Akses: ' + (activeMaterial?.access ?? '')"></span>
                        <span class="badge bg-brand-50 px-2.5 py-1 text-brand-700" x-text="'Status: ' + (activeMaterial?.status ?? '')"></span>
                    </div>
                    <div class="prose-materi text-[15px]" x-html="activeMaterial?.contentHtml"></div>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-line bg-canvas/60 px-5 py-3.5">
                    <a :href="activeMaterial?.editUrl" wire:navigate x-on:click="activeMaterial = null" class="btn btn-outline btn-sm text-sm">Ubah materi</a>
                    <button type="button" x-on:click="activeMaterial = null" class="btn btn-primary btn-sm text-sm">Tutup</button>
                </div>
            </div>
        </div>
    </template>

    {{-- Modal Pratinjau Soal --}}
    <template x-teleport="body">
        <div x-cloak x-show="activeQuestion !== null" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4 backdrop-blur-xs" x-on:click.self="activeQuestion = null" role="dialog" aria-modal="true">
            <div x-show="activeQuestion !== null" x-transition class="flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-line bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-line px-5 py-4">
                    <div>
                        <h2 class="text-lg font-extrabold text-ink" x-text="'Soal #' + (activeQuestion?.id ?? '') + ' · ' + (activeQuestion?.type ?? '')"></h2>
                        <p class="mt-0.5 text-sm text-muted" x-text="activeQuestion?.location"></p>
                    </div>
                    <button type="button" x-on:click="activeQuestion = null" class="grid size-8 flex-none place-items-center rounded-lg text-lg text-subtle hover:bg-navy-50">×</button>
                </div>
                <div class="space-y-4 overflow-y-auto px-5 py-4">
                    <div class="prose-materi text-[15px]" x-html="activeQuestion?.bodyHtml"></div>
                    <div x-show="activeQuestion && activeQuestion.options.length > 0" class="space-y-2">
                        <template x-for="(opt, idx) in (activeQuestion?.options ?? [])" :key="idx">
                            <div class="flex items-start justify-between gap-3 rounded-xl border px-3.5 py-2.5 text-sm" :class="opt.isCorrect ? 'border-brand-500 bg-brand-50/70 font-semibold text-ink' : 'border-line text-muted'">
                                <span><strong class="mr-1.5 text-ink" x-text="opt.label + '.'"></strong><span x-text="opt.body"></span></span>
                                <span class="whitespace-nowrap text-xs font-bold" :class="opt.isCorrect ? 'text-brand-700' : 'text-subtle'" x-text="opt.isCorrect ? 'Benar' : (opt.weight ? 'Bobot ' + opt.weight : '')"></span>
                            </div>
                        </template>
                    </div>
                    <div x-show="activeQuestion?.explanationHtml" class="rounded-xl bg-canvas p-4">
                        <p class="mb-1 text-xs font-bold tracking-wide text-subtle uppercase">Pembahasan</p>
                        <div class="prose-materi text-sm" x-html="activeQuestion?.explanationHtml"></div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-line bg-canvas/60 px-5 py-3.5">
                    <a :href="activeQuestion?.editUrl" wire:navigate x-on:click="activeQuestion = null" class="btn btn-outline btn-sm text-sm">Ubah soal</a>
                    <button type="button" x-on:click="activeQuestion = null" class="btn btn-primary btn-sm text-sm">Tutup</button>
                </div>
            </div>
        </div>
    </template>

    {{-- Modal Bagian Paket Try Out --}}
    <template x-teleport="body">
        <div x-cloak x-show="activePackage !== null" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4 backdrop-blur-xs" x-on:click.self="activePackage = null" role="dialog" aria-modal="true">
            <div x-show="activePackage !== null" x-transition class="w-full max-w-md overflow-hidden rounded-2xl border border-line bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-line px-5 py-4">
                    <div>
                        <h2 class="text-lg font-extrabold text-ink" x-text="activePackage?.title"></h2>
                        <p class="mt-0.5 text-sm text-muted" x-text="(activePackage?.duration ?? '') + ' · ' + (activePackage?.attempts ?? '')"></p>
                    </div>
                    <button type="button" x-on:click="activePackage = null" class="grid size-8 flex-none place-items-center rounded-lg text-lg text-subtle hover:bg-navy-50">×</button>
                </div>
                <div class="divide-y divide-line px-5">
                    <template x-for="(sec, idx) in (activePackage?.sections ?? [])" :key="idx">
                        <div class="flex items-center justify-between gap-4 py-3.5">
                            <div>
                                <p class="text-base font-bold text-ink" x-text="sec.name"></p>
                                <p x-show="sec.passingScore" class="text-sm text-muted" x-text="sec.passingScore"></p>
                            </div>
                            <span class="badge bg-navy-50 px-3 py-1 text-sm text-ink" x-text="sec.questions"></span>
                        </div>
                    </template>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-line bg-canvas/60 px-5 py-3.5">
                    <a :href="activePackage?.editUrl" wire:navigate x-on:click="activePackage = null" class="btn btn-outline btn-sm text-sm">Kelola paket</a>
                    <button type="button" x-on:click="activePackage = null" class="btn btn-primary btn-sm text-sm">Tutup</button>
                </div>
            </div>
        </div>
    </template>
</div>
