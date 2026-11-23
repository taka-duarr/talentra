<?php

namespace App\Livewire\Admin\Attempts;

use App\Models\ExamAttempt;
use App\Models\Question;
use App\Services\Exam\ExamScorer;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin')]
class AttemptShow extends Component
{
    public ExamAttempt $attempt;

    public function mount(ExamAttempt $attempt): void
    {
        $this->attempt = $attempt->load(['user.targetCategory', 'package.category', 'analysis']);
    }

    public function render(ExamScorer $scorer): View
    {
        $order = collect($this->attempt->question_order);
        $questions = Question::query()->with(['options', 'topic'])->whereIn('id', $order->pluck('q'))->get()->keyBy('id');
        $answers = $this->attempt->answers()->get()->keyBy('question_id');

        $items = $order->values()->map(fn (array $entry, int $index) => [
            'number' => $index + 1,
            'question' => $questions->get($entry['q']),
            'answer' => $answers->get($entry['q']),
        ])->filter(fn (array $item) => $item['question'] !== null);

        $otherAttempts = ExamAttempt::query()
            ->where('user_id', $this->attempt->user_id)
            ->whereKeyNot($this->attempt->id)
            ->with('package')
            ->latest('started_at')
            ->take(5)
            ->get();

        return view('livewire.admin.attempts.show', [
            'items' => $items,
            'topics' => $this->attempt->isInProgress() ? collect() : $scorer->topicBreakdown($this->attempt),
            'otherAttempts' => $otherAttempts,
        ])->title('Hasil '.$this->attempt->user->name);
    }
}
