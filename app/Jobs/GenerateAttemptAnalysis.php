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

    public int $timeout = 90;

    public function __construct(public int $attemptId) {}

    public function handle(AttemptAnalyzer $analyzer): void
    {
        $attempt = ExamAttempt::query()->find($this->attemptId);

        if ($attempt !== null && ! $attempt->isInProgress()) {
            $analyzer->analyze($attempt);
        }
    }
}
