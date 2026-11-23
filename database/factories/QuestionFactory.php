<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'type' => QuestionType::SingleChoice,
            'body' => '<p>'.fake()->sentence().'?</p>',
            'explanation' => '<p>'.fake()->sentence().'</p>',
            'difficulty' => 2,
            'status' => ContentStatus::Published,
        ];
    }

    /**
     * Single choice question with five options, the first one correct.
     */
    public function withOptions(int $count = 5): static
    {
        return $this->afterCreating(function (Question $question) use ($count) {
            foreach (range(0, $count - 1) as $index) {
                QuestionOption::factory()->for($question)->create([
                    'is_correct' => $index === 0,
                    'sort_order' => $index,
                ]);
            }
        });
    }

    /**
     * TKP style question where every option carries a weight from 1 to 5.
     */
    public function weighted(): static
    {
        return $this->state(fn (array $attributes) => ['type' => QuestionType::Weighted])
            ->afterCreating(function (Question $question) {
                foreach ([5, 4, 3, 2, 1] as $index => $weight) {
                    QuestionOption::factory()->for($question)->create([
                        'weight' => $weight,
                        'sort_order' => $index,
                    ]);
                }
            });
    }
}
