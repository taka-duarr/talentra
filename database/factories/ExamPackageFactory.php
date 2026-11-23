<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Category;
use App\Models\ExamPackage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExamPackage>
 */
class ExamPackageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = 'Try Out '.fake()->unique()->words(2, true);

        return [
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->sentence(),
            'duration_minutes' => 60,
            'is_free' => false,
            'shuffle_questions' => false,
            'shuffle_options' => false,
            'counts_for_ranking' => true,
            'score_correct' => 4,
            'score_wrong' => 0,
            'score_blank' => 0,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ];
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => ['is_free' => true]);
    }
}
