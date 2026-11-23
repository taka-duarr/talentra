<?php

namespace Database\Factories;

use App\Enums\AttemptStatus;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAttempt>
 */
class ExamAttemptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'exam_package_id' => ExamPackage::factory(),
            'status' => AttemptStatus::InProgress,
            'started_at' => now(),
            'ends_at' => now()->addHour(),
            'question_order' => [],
        ];
    }
}
