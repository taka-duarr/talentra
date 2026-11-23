<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Local development data. Login with password "password" for every account below.
     */
    public function run(): void
    {
        $this->call([
            CatalogSeeder::class,
            QuestionBankSeeder::class,
            PlanSeeder::class,
        ]);

        $admin = User::query()->updateOrCreate(['email' => 'admin@talentra.test'], [
            'name' => 'Admin Talentra',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);
        $admin->forceFill(['role' => UserRole::SuperAdmin])->save();

        $contentAdmin = User::query()->updateOrCreate(['email' => 'konten@talentra.test'], [
            'name' => 'Admin Konten',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);
        $contentAdmin->forceFill(['role' => UserRole::ContentAdmin])->save();

        User::query()->updateOrCreate(['email' => 'siswa@talentra.test'], [
            'name' => 'Siswa Demo',
            'password' => 'password',
            'email_verified_at' => now(),
            'target_category_id' => Category::query()->where('slug', 'cpns')->value('id'),
        ]);

        $this->call(CommunitySeeder::class);
    }
}
