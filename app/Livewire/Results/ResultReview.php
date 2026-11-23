<?php

namespace App\Livewire\Results;

use App\Models\ExamAttempt;
use App\Models\Question;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts::app')]
class ResultReview extends Component
{
    public ExamAttempt $attempt;

    #[Url(as: 'tampil')]
    public string $filter = 'semua';

    /**
     * Free demo viewers see explanations for this many questions only.
     */
    public const DEMO_EXPLANATIONS = 2;

    public function mount(ExamAttempt $attempt): void
    {
        abort_unless($attempt->user_id === auth()->id() || auth()->user()->isAdmin(), 403);
        abort_if($attempt->isInProgress(), 404);

        $this->attempt = $attempt->load('package');
    }

    public function render(): View
    {
        $order = collect($this->attempt->question_order);
        $questions = Question::query()->with(['options', 'topic'])->whereIn('id', $order->pluck('q'))->get()->keyBy('id');
        $answers = $this->attempt->answers()->get()->keyBy('question_id');

        $items = $order->values()->map(fn (array $entry, int $index) => [
            'number' => $index + 1,
            'question' => $questions->get($entry['q']),
            'answer' => $answers->get($entry['q']),
        ])->filter(fn (array $item) => $item['question'] !== null);

        $items = $items->filter(fn (array $item) => match ($this->filter) {
            'salah' => $item['answer']?->is_correct === false,
            'benar' => $item['answer']?->is_correct === true,
            'kosong' => $item['answer'] === null || $item['answer']->is_correct === null,
            'ragu' => (bool) $item['answer']?->is_flagged,
            default => true,
        });

        $user = auth()->user();

        return view('livewire.results.review', [
            'items' => $items,
            'limitExplanations' => $this->attempt->package->is_free && ! $user->canAccessCategory($this->attempt->package->category_id),
        ])->title('Pembahasan '.$this->attempt->package->title);
    }
}
