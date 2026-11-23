<?php

namespace Database\Factories;

use App\Models\ExamPackage;
use App\Models\ExamSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamSection>
 */
class ExamSectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_package_id' => ExamPackage::factory(),
            'name' => fake()->words(2, true),
            'sort_order' => 0,
        ];
    }
}
