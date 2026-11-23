<?php

namespace Database\Factories;

use App\Models\Material;
use App\Models\MaterialProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaterialProgress>
 */
class MaterialProgressFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'material_id' => Material::factory(),
            'completed_at' => now(),
        ];
    }
}
