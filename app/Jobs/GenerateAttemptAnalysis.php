<?php

namespace App\Jobs;

use App\Models\ExamAttempt;
use App\Services\Analysis\AttemptAnalyzer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateAttemptAnalysis implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    /**
     * Room for the primary model plus one fallback model.
     */
    public int $timeout = 200;

    public function __construct(public int $attemptId) {}

    public function handle(AttemptAnalyzer $analyzer): void
    {
        // When run after the response (no queue worker) the web request's time limit still applies.
        set_time_limit($this->timeout + 30);

        $attempt = ExamAttempt::query()->find($this->attemptId);

        if ($attempt !== null && ! $attempt->isInProgress()) {
            $analyzer->analyze($attempt);
        }
    }
}
