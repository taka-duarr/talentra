<?php

namespace Database\Factories;

use App\Models\ForumThread;
use App\Models\ForumVote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ForumVote>
 */
class ForumVoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'votable_type' => ForumThread::class,
            'votable_id' => ForumThread::factory(),
        ];
    }
}
