<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserPoint;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserPoint>
 */
class UserPointFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'points' => 10,
            'reason' => 'tryout_completed',
            'source_key' => (string) Str::uuid(),
            'created_at' => now(),
        ];
    }
}
