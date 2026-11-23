<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Paket '.fake()->unique()->word();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'is_all_access' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function allAccess(): static
    {
        return $this->state(fn (array $attributes) => ['is_all_access' => true]);
    }
}
