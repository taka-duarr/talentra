<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'TLN-'.Str::upper(Str::random(8)),
            'user_id' => User::factory(),
            'plan_id' => Plan::factory(),
            'months' => 1,
            'subtotal' => 49000,
            'discount' => 0,
            'total' => 49000,
            'status' => OrderStatus::Pending,
            'gateway' => 'manual',
            'expires_at' => now()->addDay(),
        ];
    }
}
