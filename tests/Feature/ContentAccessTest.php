<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Html;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_material_is_readable_and_paid_material_is_locked(): void
    {
        $user = User::factory()->create();
        $free = Material::factory()->free()->create(['content' => '<p>Isi materi gratis</p>']);
        $paid = Material::factory()->create(['content' => '<p>Isi materi berbayar</p>']);

        $this->actingAs($user)->get(route('learn.material', $free))->assertOk()->assertSee('Isi materi gratis', false);
        $this->get(route('learn.material', $paid))->assertOk()->assertDontSee('Isi materi berbayar', false)->assertSee('khusus pelanggan');
    }

    public function test_all_access_subscription_unlocks_every_category(): void
    {
        $user = User::factory()->create();
        $paid = Material::factory()->create(['content' => '<p>Isi materi berbayar</p>']);
        Subscription::factory()->for($user)->for(Plan::factory()->allAccess())->create();

        $this->actingAs($user)->get(route('learn.material', $paid))->assertSee('Isi materi berbayar', false);
    }

    public function test_expired_subscription_does_not_unlock(): void
    {
        $user = User::factory()->create();
        $paid = Material::factory()->create();
        Subscription::factory()->expired()->for($user)->for(Plan::factory()->allAccess())->create();

        $this->assertFalse($paid->isAccessibleBy($user));
    }

    public function test_user_pages_render(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'siswa@talentra.test')->firstOrFail();

        foreach (['dashboard', 'learn.index', 'tryout.index', 'plans.index', 'orders.index', 'profile.edit'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }
    }

    public function test_html_sanitizer_strips_scripts_and_event_handlers(): void
    {
        $clean = Html::clean('<p onclick="x()">Halo<script>alert(1)</script><a href="javascript:alert(1)">tautan</a></p>');

        $this->assertStringNotContainsString('script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringContainsString('Halo', $clean);
    }
}
