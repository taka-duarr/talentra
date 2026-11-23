<?php

namespace Database\Factories;

use App\Enums\AnalysisStatus;
use App\Models\AiAnalysis;
use App\Models\ExamAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiAnalysis>
 */
class AiAnalysisFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_attempt_id' => ExamAttempt::factory(),
            'status' => AnalysisStatus::Pending,
        ];
    }
}
