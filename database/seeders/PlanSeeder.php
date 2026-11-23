<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlanSeeder extends Seeder
{
    /**
     * One plan per category plus an all-access plan. Prices are placeholders until the team decides.
     */
    public function run(): void
    {
        $categoryPrices = [1 => 49000, 3 => 129000, 6 => 239000, 12 => 429000];
        $allAccessPrices = [1 => 99000, 3 => 269000, 6 => 499000, 12 => 899000];

        foreach (Category::query()->ordered()->get() as $index => $category) {
            $plan = Plan::query()->updateOrCreate(['slug' => Str::slug('paket '.$category->name)], [
                'name' => 'Paket '.$category->name,
                'description' => 'Semua materi dan try out '.$category->name.'.',
                'is_all_access' => false,
                'sort_order' => $index + 1,
            ]);
            $plan->categories()->sync([$category->id]);
            $this->syncPrices($plan, $categoryPrices);
        }

        $allAccess = Plan::query()->updateOrCreate(['slug' => 'semua-akses'], [
            'name' => 'Semua Akses',
            'description' => 'Satu langganan untuk semua kategori ujian.',
            'is_all_access' => true,
            'sort_order' => 0,
        ]);
        $allAccess->categories()->sync(Category::query()->pluck('id'));
        $this->syncPrices($allAccess, $allAccessPrices);
    }

    /**
     * @param  array<int, int>  $prices
     */
    private function syncPrices(Plan $plan, array $prices): void
    {
        foreach ($prices as $months => $price) {
            $plan->prices()->updateOrCreate(['months' => $months], [
                'price' => $price,
                'compare_at_price' => $months > 1 ? $prices[1] * $months : null,
            ]);
        }
    }
}
