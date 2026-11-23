<?php

namespace Database\Factories;

use App\Enums\ThreadScope;
use App\Enums\ThreadTag;
use App\Models\ForumThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ForumThread>
 */
class ForumThreadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'scope' => ThreadScope::Global,
            'title' => fake()->sentence(6),
            'body' => fake()->paragraph(),
            'tag' => ThreadTag::Tanya,
            'last_activity_at' => now(),
        ];
    }
}
