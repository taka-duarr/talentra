<?php

namespace App\Services\Exam;

use App\Enums\AttemptStatus;
use App\Enums\QuestionType;
use App\Jobs\GenerateAttemptAnalysis;
use App\Models\AttemptAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\Question;
use App\Models\User;
use App\Services\Community\PointService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamService
{
    /**
     * Seconds after ends_at during which in-flight autosaves are still accepted.
     */
    public const GRACE_SECONDS = 10;

    public function __construct(private ExamScorer $scorer, private PointService $points) {}

    /**
     * Resumes the user's running attempt for the package, or starts a new one.
     */
    public function start(User $user, ExamPackage $package): ExamAttempt
    {
        if (! $package->isAccessibleBy($user)) {
            throw new AuthorizationException('Paket ini membutuhkan langganan aktif.');
        }

        $running = $package->attempts()
            ->where('user_id', $user->id)
            ->where('status', AttemptStatus::InProgress)
            ->latest('id')
            ->first();

        if ($running !== null) {
            if (! $this->finalizeIfExpired($running)) {
                return $running;
            }
        }

        if (! $package->hasAttemptsLeftFor($user)) {
            throw ValidationException::withMessages(['package' => 'Kesempatan mengerjakan paket ini sudah habis.']);
        }

        if (! $package->arePrerequisitesMetBy($user)) {
            $remaining = $package->uncompletedMaterialsFor($user)->count();

            throw ValidationException::withMessages([
                'package' => "Selesaikan {$remaining} materi kursus terlebih dahulu sebelum mengerjakan try out ini.",
            ]);
        }

        // Only one exam may run at a time per user.
        $otherRunning = ExamAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', AttemptStatus::InProgress)
            ->with('package')
            ->get()
            ->reject(fn (ExamAttempt $attempt) => $this->finalizeIfExpired($attempt));

        if ($otherRunning->isNotEmpty()) {
            throw ValidationException::withMessages([
                'package' => 'Selesaikan dulu ujian "'.$otherRunning->first()->package->title.'" yang sedang berjalan.',
            ]);
        }

        $package->load('sections.questions.options');
        [$questionOrder, $optionOrder] = $this->buildOrder($package);

        if ($questionOrder === []) {
            throw ValidationException::withMessages(['package' => 'Paket ini belum memiliki soal.']);
        }

        return ExamAttempt::create([
            'user_id' => $user->id,
            'exam_package_id' => $package->id,
            'status' => AttemptStatus::InProgress,
            'started_at' => now(),
            'ends_at' => now()->addMinutes($package->duration_minutes),
            'question_order' => $questionOrder,
            'option_order' => $optionOrder,
            'is_first_attempt' => ! $package->attempts()->where('user_id', $user->id)->exists(),
        ]);
    }

    /**
     * @param  array{selected_option_ids?: array<int, int>|null, statement_answers?: array<int|string, bool|string>|null, answer_text?: string|null, is_flagged?: bool}  $payload
     */
    public function saveAnswer(ExamAttempt $attempt, int $questionId, array $payload): AttemptAnswer
    {
        $this->ensureAcceptingAnswers($attempt);

        if (! collect($attempt->question_order)->pluck('q')->contains($questionId)) {
            throw ValidationException::withMessages(['question_id' => 'Soal tidak termasuk dalam ujian ini.']);
        }

        $question = Question::query()->with('options')->findOrFail($questionId);
        $optionIds = $question->options->pluck('id');

        $selected = collect($payload['selected_option_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();

        if ($selected->diff($optionIds)->isNotEmpty()) {
            throw ValidationException::withMessages(['selected_option_ids' => 'Pilihan jawaban tidak valid.']);
        }

        if (in_array($question->type, [QuestionType::SingleChoice, QuestionType::Weighted], true) && $selected->count() > 1) {
            $selected = $selected->take(1);
        }

        $statements = collect($payload['statement_answers'] ?? [])
            ->filter(fn ($value, $key) => $optionIds->contains((int) $key) && $value !== null)
            ->mapWithKeys(fn ($value, $key) => [(string) $key => filter_var($value, FILTER_VALIDATE_BOOLEAN)]);

        $values = [
            'selected_option_ids' => $question->type->usesOptions() && $question->type !== QuestionType::TrueFalse ? $selected->all() : null,
            'statement_answers' => $question->type === QuestionType::TrueFalse ? $statements->all() : null,
            'answer_text' => $question->type === QuestionType::ShortAnswer ? mb_substr(trim((string) ($payload['answer_text'] ?? '')), 0, 255) : null,
            'answered_at' => now(),
        ];

        if (array_key_exists('is_flagged', $payload)) {
            $values['is_flagged'] = (bool) $payload['is_flagged'];
        }

        return AttemptAnswer::query()->updateOrCreate(
            ['exam_attempt_id' => $attempt->id, 'question_id' => $questionId],
            $values,
        );
    }

    public function setFlag(ExamAttempt $attempt, int $questionId, bool $flagged): void
    {
        $this->ensureAcceptingAnswers($attempt);

        if (! collect($attempt->question_order)->pluck('q')->contains($questionId)) {
            throw ValidationException::withMessages(['question_id' => 'Soal tidak termasuk dalam ujian ini.']);
        }

        AttemptAnswer::query()->updateOrCreate(
            ['exam_attempt_id' => $attempt->id, 'question_id' => $questionId],
            ['is_flagged' => $flagged],
        );
    }

    public function recordTabSwitch(ExamAttempt $attempt): void
    {
        if ($attempt->isInProgress()) {
            $attempt->increment('tab_switches');
        }
    }

    public function submit(ExamAttempt $attempt): ExamAttempt
    {
        $attempt = DB::transaction(function () use ($attempt) {
            $locked = ExamAttempt::query()->lockForUpdate()->findOrFail($attempt->id);

            if (! $locked->isInProgress()) {
                return $locked;
            }

            $this->scorer->score($locked);

            $locked->forceFill([
                'status' => AttemptStatus::Submitted,
                'submitted_at' => now()->min($locked->ends_at),
            ])->save();

            return $locked;
        });

        if ($attempt->wasChanged('status')) {
            $this->points->awardForAttempt($attempt);

            config('talentra.analysis.use_queue')
                ? GenerateAttemptAnalysis::dispatch($attempt->id)
                : GenerateAttemptAnalysis::dispatchAfterResponse($attempt->id);
        }

        return $attempt;
    }

    /**
     * Submits the attempt if its time (plus grace) is over. Returns true when it was finalised.
     */
    public function finalizeIfExpired(ExamAttempt $attempt): bool
    {
        if ($attempt->isInProgress() && now()->greaterThan($attempt->ends_at->copy()->addSeconds(self::GRACE_SECONDS))) {
            $this->submit($attempt);

            return true;
        }

        return ! $attempt->isInProgress();
    }

    private function ensureAcceptingAnswers(ExamAttempt $attempt): void
    {
        if (! $attempt->isInProgress() || now()->greaterThan($attempt->ends_at->copy()->addSeconds(self::GRACE_SECONDS))) {
            throw ValidationException::withMessages(['attempt' => 'Waktu ujian sudah habis.']);
        }
    }

    /**
     * @return array{0: array<int, array{q: int, s: int}>, 1: array<string, array<int, int>>|null}
     */
    private function buildOrder(ExamPackage $package): array
    {
        $questionOrder = [];
        $optionOrder = [];

        foreach ($package->sections as $section) {
            $questions = $section->questions->filter(fn (Question $question) => $question->status->value === 'published');

            if ($package->shuffle_questions) {
                $questions = $questions->shuffle();
            }

            foreach ($questions as $question) {
                $questionOrder[] = ['q' => $question->id, 's' => $section->id];

                if ($package->shuffle_options && $question->type !== QuestionType::TrueFalse) {
                    $optionOrder[(string) $question->id] = $question->options->pluck('id')->shuffle()->values()->all();
                }
            }
        }

        // Practice packages draw a random subset from a larger pool.
        if ($package->question_limit && count($questionOrder) > $package->question_limit) {
            $questionOrder = collect($questionOrder)->shuffle()->take($package->question_limit)->values()->all();
            $kept = collect($questionOrder)->pluck('q')->map(fn ($id) => (string) $id);
            $optionOrder = array_intersect_key($optionOrder, array_flip($kept->all()));
        }

        return [$questionOrder, $optionOrder === [] ? null : $optionOrder];
    }
}
