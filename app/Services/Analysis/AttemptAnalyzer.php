<?php

namespace App\Services\Analysis;

use App\Enums\AnalysisStatus;
use App\Models\AiAnalysis;
use App\Models\ExamAttempt;
use Illuminate\Support\Facades\Log;
use Throwable;

class AttemptAnalyzer
{
    public function __construct(
        private AnalysisContextBuilder $contextBuilder,
        private AnalysisDriver $driver,
        private RuleBasedAnalysisDriver $fallback,
    ) {}

    /**
     * Generates (or regenerates) the stored analysis for a submitted attempt.
     */
    public function analyze(ExamAttempt $attempt): AiAnalysis
    {
        $analysis = AiAnalysis::query()->firstOrCreate(
            ['exam_attempt_id' => $attempt->id],
            ['status' => AnalysisStatus::Pending],
        );

        $context = $this->contextBuilder->build($attempt);
        $driver = $this->withinDailyLimit($attempt) ? $this->driver : $this->fallback;
        $error = null;

        try {
            $result = $driver->analyze($context);
        } catch (Throwable $exception) {
            Log::warning('AI analysis failed, using rule-based analysis', [
                'attempt_id' => $attempt->id,
                'message' => $exception->getMessage(),
            ]);
            $error = $exception->getMessage();
            $result = $this->fallback->analyze($context);
        }

        $analysis->forceFill([
            'status' => AnalysisStatus::Completed,
            'source' => $result->source,
            'result' => $result->data,
            'model' => $result->model,
            'input_tokens' => $result->inputTokens,
            'output_tokens' => $result->outputTokens,
            'error' => $error,
        ])->save();

        return $analysis;
    }

    private function withinDailyLimit(ExamAttempt $attempt): bool
    {
        $usedToday = AiAnalysis::query()
            ->where('source', 'ai')
            ->whereDate('created_at', today())
            ->whereHas('attempt', fn ($query) => $query->where('user_id', $attempt->user_id))
            ->count();

        return $usedToday < config('talentra.analysis.daily_limit_per_user');
    }
}
