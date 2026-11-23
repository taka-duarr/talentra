<?php

namespace Database\Seeders;

use App\Enums\ThreadScope;
use App\Enums\ThreadTag;
use App\Models\ForumThread;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommunitySeeder extends Seeder
{
    /**
     * A pinned welcome thread so the forum is not empty on first visit.
     */
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@talentra.test')->first();

        if ($admin === null) {
            return;
        }

        $admin->forceFill(['forum_guidelines_accepted_at' => $admin->forum_guidelines_accepted_at ?? now()])->save();

        $thread = ForumThread::query()->firstOrCreate(
            ['scope' => ThreadScope::Global, 'title' => 'Selamat datang di forum Talentra 👋'],
            [
                'user_id' => $admin->id,
                'body' => "Halo pejuang ujian!\n\nForum ini tempat bertanya, berbagi tips, dan membahas soal sulit bersama. Sebelum menulis, baca pedoman komunitas: saling menghormati, tidak membagikan soal rahasia, dan tidak promosi.\n\nPerkenalkan dirimu di balasan: ujian apa yang sedang kamu persiapkan?",
                'tag' => ThreadTag::Info,
                'last_activity_at' => now(),
            ],
        );

        $thread->forceFill(['is_pinned' => true])->save();
    }
}
