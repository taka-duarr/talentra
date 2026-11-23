<?php

namespace Database\Factories;

use App\Models\ForumReport;
use App\Models\ForumThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ForumReport>
 */
class ForumReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reportable_type' => ForumThread::class,
            'reportable_id' => ForumThread::factory(),
            'reason' => 'Spam',
        ];
    }
}
