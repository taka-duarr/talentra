<?php

namespace App\Livewire\Admin;

use App\Enums\AttemptStatus;
use App\Enums\ContentStatus;
use App\Enums\OrderStatus;
use App\Models\AiAnalysis;
use App\Models\ExamAttempt;
use App\Models\Order;
use App\Models\Question;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Ringkasan')]
class AdminDashboard extends Component
{
    public function render(): View
    {
        $revenueByMonth = Order::query()
            ->where('status', OrderStatus::Paid)
            ->where('paid_at', '>=', now()->subMonths(5)->startOfMonth())
            ->get(['total', 'paid_at'])
            ->groupBy(fn (Order $order) => $order->paid_at->format('Y-m'))
            ->map->sum('total');

        $months = collect(range(5, 0))->map(fn (int $offset) => now()->subMonths($offset));

        $hardestQuestions = DB::table('attempt_answers')
            ->join('questions', 'questions.id', '=', 'attempt_answers.question_id')
            ->whereNotNull('attempt_answers.is_correct')
            ->groupBy('attempt_answers.question_id', 'questions.body')
            ->havingRaw('count(*) >= 3')
            ->selectRaw('attempt_answers.question_id, questions.body, count(*) as total, sum(case when attempt_answers.is_correct = 1 then 1 else 0 end) as correct')
            ->orderByRaw('sum(case when attempt_answers.is_correct = 1 then 1 else 0 end) / count(*)')
            ->limit(5)
            ->get();

        return view('livewire.admin.dashboard', [
            'stats' => [
                'newUsers' => User::query()->where('created_at', '>=', now()->subDays(30))->count(),
                'activeSubscribers' => Subscription::query()->active()->distinct('user_id')->count('user_id'),
                'pendingOrders' => Order::query()->where('status', OrderStatus::Pending)->whereNotNull('proof_path')->count(),
                'revenueThisMonth' => (int) Order::query()->where('status', OrderStatus::Paid)->where('paid_at', '>=', now()->startOfMonth())->sum('total'),
                'attemptsToday' => ExamAttempt::query()->where('status', AttemptStatus::Submitted)->whereDate('submitted_at', today())->count(),
                'questionsInReview' => Question::query()->where('status', ContentStatus::Review)->count(),
                'aiAnalysesToday' => AiAnalysis::query()->where('source', 'ai')->whereDate('created_at', today())->count(),
                'aiTokensMonth' => (int) AiAnalysis::query()->where('created_at', '>=', now()->startOfMonth())->sum(DB::raw('input_tokens + output_tokens')),
            ],
            'revenueChart' => $months->map(fn ($month) => [
                'label' => $month->translatedFormat('M'),
                'value' => (int) ($revenueByMonth[$month->format('Y-m')] ?? 0),
            ]),
            'hardestQuestions' => $hardestQuestions,
            'isSuper' => auth()->user()->isSuperAdmin(),
        ]);
    }
}
