<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'chapter_id' => Chapter::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'is_free' => false,
            'sort_order' => 0,
            'published_at' => now(),
        ];
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => ['is_free' => true]);
    }
}
