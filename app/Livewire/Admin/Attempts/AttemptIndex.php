<?php

namespace App\Livewire\Admin\Attempts;

use App\Enums\AttemptStatus;
use App\Models\Category;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Lets admins monitor every user's exam attempts.
 */
#[Layout('layouts::admin')]
#[Title('Hasil peserta')]
class AttemptIndex extends Component
{
    use WithPagination;

    #[Url(as: 'cari')]
    public string $search = '';

    #[Url(as: 'kategori')]
    public string $category = '';

    #[Url(as: 'paket')]
    public string $package = '';

    #[Url]
    public string $status = '';

    #[Url(as: 'hasil')]
    public string $result = '';

    #[Url(as: 'periode')]
    public string $period = '30';

    public function updated(): void
    {
        $this->resetPage();
    }

    /**
     * @return Builder<ExamAttempt>
     */
    private function filtered(): Builder
    {
        return ExamAttempt::query()
            ->when($this->search !== '', fn ($query) => $query->whereHas('user', fn ($user) => $user
                ->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('email', 'like', '%'.$this->search.'%')))
            ->when($this->category !== '', fn ($query) => $query->whereHas('package', fn ($package) => $package->where('category_id', $this->category)))
            ->when($this->package !== '', fn ($query) => $query->where('exam_package_id', $this->package))
            ->when($this->status === 'selesai', fn ($query) => $query->where('status', AttemptStatus::Submitted))
            ->when($this->status === 'berjalan', fn ($query) => $query->where('status', AttemptStatus::InProgress))
            ->when($this->result === 'lolos', fn ($query) => $query->where('passed', true))
            ->when($this->result === 'belum', fn ($query) => $query->where('passed', false))
            ->when(in_array($this->period, ['1', '7', '30'], true), fn ($query) => $query->where('started_at', '>=', now()->subDays((int) $this->period)));
    }

    public function render(): View
    {
        $summary = $this->filtered()
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when status = 'in_progress' then 1 else 0 end) as running")
            ->selectRaw("avg(case when status = 'submitted' and max_score > 0 then score / max_score * 100 end) as average")
            ->selectRaw('sum(case when passed = 1 then 1 else 0 end) as passed_count')
            ->selectRaw('sum(case when passed is not null then 1 else 0 end) as judged_count')
            ->first();

        return view('livewire.admin.attempts.index', [
            'attempts' => $this->filtered()
                ->with(['user', 'package.category'])
                ->latest('started_at')
                ->paginate(25),
            'summary' => [
                'total' => (int) $summary->total,
                'running' => (int) $summary->running,
                'average' => $summary->average !== null ? round((float) $summary->average, 1) : null,
                'passRate' => $summary->judged_count > 0 ? round($summary->passed_count / $summary->judged_count * 100) : null,
            ],
            'categories' => Category::query()->ordered()->get(['id', 'name']),
            'packages' => ExamPackage::query()
                ->when($this->category !== '', fn ($query) => $query->where('category_id', $this->category))
                ->orderBy('is_practice')
                ->orderBy('title')
                ->get(['id', 'title', 'is_practice']),
            'topPackages' => DB::table('exam_attempts')
                ->join('exam_packages', 'exam_packages.id', '=', 'exam_attempts.exam_package_id')
                ->where('exam_attempts.started_at', '>=', now()->subDays(30))
                ->groupBy('exam_packages.id', 'exam_packages.title')
                ->orderByDesc(DB::raw('count(*)'))
                ->limit(3)
                ->get(['exam_packages.id', 'exam_packages.title', DB::raw('count(*) as total')]),
        ]);
    }
}
