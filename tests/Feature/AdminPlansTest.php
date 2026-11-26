<?php

namespace Tests\Feature;

use App\Livewire\Admin\Plans;
use App\Livewire\Admin\Plans\PlanForm;
use App\Models\Category;
use App\Models\Order;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPlansTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_and_edit_plan_on_separate_pages(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.plans.create'))
            ->assertOk();

        Livewire::actingAs($admin)
            ->test(PlanForm::class)
            ->set('form.name', 'Paket Kedinasan Pro')
            ->set('form.description', 'Akses penuh materi kedinasan')
            ->set('categoryIds', [$category->id])
            ->set('prices', [
                ['months' => 1, 'price' => 55000, 'compare_at_price' => 75000],
                ['months' => 3, 'price' => 145000, 'compare_at_price' => null],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.plans'));

        $plan = Plan::query()->where('name', 'Paket Kedinasan Pro')->with(['prices', 'categories'])->sole();
        $this->assertCount(2, $plan->prices);
        $this->assertTrue($plan->categories->contains($category));

        $this->actingAs($admin)
            ->get(route('admin.plans.edit', $plan))
            ->assertOk();

        Livewire::actingAs($admin)
            ->test(PlanForm::class, ['plan' => $plan])
            ->set('form.name', 'Paket Kedinasan Plus')
            ->set('prices', [
                ['months' => 1, 'price' => 60000, 'compare_at_price' => null],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.plans'));

        $plan->refresh();
        $this->assertSame('Paket Kedinasan Plus', $plan->name);
        $this->assertCount(1, $plan->prices);
        $this->assertSame(60000, $plan->prices->first()->price);
    }

    public function test_super_admin_can_toggle_and_safely_delete_plans(): void
    {
        $admin = User::factory()->admin()->create();
        $unusedPlan = Plan::factory()->create(['is_active' => true]);
        $usedPlan = Plan::factory()->create(['is_active' => true]);
        PlanPrice::factory()->for($usedPlan)->create(['months' => 1, 'price' => 50000]);
        Order::factory()->create(['plan_id' => $usedPlan->id]);

        Livewire::actingAs($admin)
            ->test(Plans::class)
            ->call('toggleActive', $unusedPlan->id)
            ->call('delete', $usedPlan->id)
            ->call('delete', $unusedPlan->id);

        $this->assertDatabaseMissing('plans', ['id' => $unusedPlan->id]);
        $this->assertDatabaseHas('plans', ['id' => $usedPlan->id]);
    }
}
