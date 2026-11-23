<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_user_can_register_with_target_exam(): void
    {
        $category = Category::factory()->create();

        Livewire::test(Register::class)
            ->set('name', 'Rina')
            ->set('email', 'rina@example.com')
            ->set('target_category_id', $category->id)
            ->set('password', 'rahasia-kuat-123')
            ->set('password_confirmation', 'rahasia-kuat-123')
            ->set('accepted_terms', true)
            ->call('register')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertSame($category->id, User::query()->sole()->target_category_id);
    }

    public function test_user_can_login_and_wrong_password_fails(): void
    {
        $user = User::factory()->create();

        Livewire::test(Login::class)->set('email', $user->email)->set('password', 'salah')->call('login')->assertHasErrors('email');
        $this->assertGuest();

        Livewire::test(Login::class)->set('email', $user->email)->set('password', 'password')->call('login')->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_blocked_user_is_logged_out(): void
    {
        $user = User::factory()->create(['blocked_at' => now()]);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_regular_user_cannot_open_admin(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }
}
