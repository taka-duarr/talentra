<?php

namespace App\Http\Controllers;

use App\Models\AttemptAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Services\Exam\ExamService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ExamController extends Controller
{
    public function __construct(private ExamService $exams) {}

    public function start(Request $request, ExamPackage $package): RedirectResponse
    {
        abort_unless($package->status->value === 'published', 404);

        try {
            $attempt = $this->exams->start($request->user(), $package);
        } catch (AuthorizationException) {
            return redirect()->route('plans.index')->with('error', 'Paket ini membutuhkan langganan aktif.');
        } catch (ValidationException $exception) {
            return back()->with('error', collect($exception->errors())->flatten()->first());
        }

        return redirect()->route('exam.show', $attempt);
    }

    public function show(Request $request, ExamAttempt $attempt): Response|HttpResponse
    {
        $this->authorizeOwner($request, $attempt);

        if ($this->exams->finalizeIfExpired($attempt)) {
            return Inertia::location(route('results.show', $attempt));
        }

        $attempt->load('package.sections');
        $order = collect($attempt->question_order);
        $questions = Question::query()->with('options')->whereIn('id', $order->pluck('q'))->get()->keyBy('id');
        $optionOrder = collect($attempt->option_order ?? []);
        $sections = $attempt->package->sections->keyBy('id');

        $items = $order->values()->map(function (array $entry, int $index) use ($questions, $optionOrder, $sections) {
            /** @var Question $question */
            $question = $questions->get($entry['q']);
            $options = $question->options;

            if ($optionOrder->has((string) $question->id)) {
                $positions = array_flip($optionOrder->get((string) $question->id));
                $options = $options->sortBy(fn (QuestionOption $option) => $positions[$option->id] ?? PHP_INT_MAX)->values();
            }

            return [
                'id' => $question->id,
                'number' => $index + 1,
                'section_id' => $entry['s'],
                'section' => $sections->get($entry['s'])?->name,
                'type' => $question->type->value,
                'hint' => $question->type->hint(),
                'body' => $question->body,
                // Never send is_correct or weight to the browser.
                'options' => $options->map(fn (QuestionOption $option) => ['id' => $option->id, 'body' => $option->body])->values(),
            ];
        });

        $answers = $attempt->answers()->get()->mapWithKeys(fn (AttemptAnswer $answer) => [$answer->question_id => [
            'selected_option_ids' => $answer->selected_option_ids ?? [],
            'statement_answers' => (object) ($answer->statement_answers ?? []),
            'answer_text' => $answer->answer_text ?? '',
            'is_flagged' => $answer->is_flagged,
        ]]);

        return Inertia::render('Exam/Take', [
            'attempt' => [
                'id' => $attempt->id,
                'remaining_seconds' => $attempt->remainingSeconds(),
                'ends_at' => $attempt->ends_at->toIso8601String(),
            ],
            'package' => [
                'title' => $attempt->package->title,
                'sections' => $attempt->package->sections->map(fn ($section) => ['id' => $section->id, 'name' => $section->name])->values(),
            ],
            'questions' => $items,
            'answers' => (object) $answers->all(),
            'urls' => [
                'answer' => route('exam.answer', $attempt),
                'flag' => route('exam.flag', $attempt),
                'activity' => route('exam.activity', $attempt),
                'submit' => route('exam.submit', $attempt),
                'exit' => route('tryout.show', $attempt->package),
            ],
        ]);
    }

    public function answer(Request $request, ExamAttempt $attempt): JsonResponse
    {
        $this->authorizeOwner($request, $attempt);

        $validated = $request->validate([
            'question_id' => ['required', 'integer'],
            'selected_option_ids' => ['nullable', 'array', 'max:20'],
            'selected_option_ids.*' => ['integer'],
            'statement_answers' => ['nullable', 'array', 'max:20'],
            'answer_text' => ['nullable', 'string', 'max:255'],
        ]);

        $this->exams->saveAnswer($attempt, (int) $validated['question_id'], $validated);

        return response()->json(['saved' => true, 'remaining_seconds' => $attempt->remainingSeconds()]);
    }

    public function flag(Request $request, ExamAttempt $attempt): JsonResponse
    {
        $this->authorizeOwner($request, $attempt);

        $validated = $request->validate([
            'question_id' => ['required', 'integer'],
            'flagged' => ['required', 'boolean'],
        ]);

        $this->exams->setFlag($attempt, (int) $validated['question_id'], (bool) $validated['flagged']);

        return response()->json(['saved' => true]);
    }

    public function activity(Request $request, ExamAttempt $attempt): JsonResponse
    {
        $this->authorizeOwner($request, $attempt);

        $this->exams->recordTabSwitch($attempt);

        return response()->json(['recorded' => true]);
    }

    public function submit(Request $request, ExamAttempt $attempt): HttpResponse
    {
        $this->authorizeOwner($request, $attempt);

        $this->exams->submit($attempt);

        return Inertia::location(route('results.show', $attempt));
    }

    private function authorizeOwner(Request $request, ExamAttempt $attempt): void
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);
    }
}
