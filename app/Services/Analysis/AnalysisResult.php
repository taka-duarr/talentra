<?php

namespace App\Services\Analysis;

final class AnalysisResult
{
    /**
     * @param  array{summary: string, strengths: array<int, string>, weaknesses: array<int, array{topic: string, reason: string}>, recommendations: array<int, array{topic: string, action: string}>, study_plan: array<int, array{day: string, task: string}>}  $data
     */
    public function __construct(
        public readonly array $data,
        public readonly string $source,
        public readonly ?string $model = null,
        public readonly int $inputTokens = 0,
        public readonly int $outputTokens = 0,
    ) {}
}
