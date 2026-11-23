<?php

namespace App\Livewire\Admin\Users;

use App\Enums\UserRole;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin')]
class UserShow extends Component
{
    public User $user;

    public ?int $grantPlanId = null;

    public int $grantMonths = 1;

    public string $role = 'user';

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->role = $user->role->value;
    }

    public function grantSubscription(): void
    {
        $this->validate([
            'grantPlanId' => ['required', Rule::exists('plans', 'id')],
            'grantMonths' => ['required', 'integer', 'between:1,36'],
        ]);

        $latestEnd = $this->user->subscriptions()->where('plan_id', $this->grantPlanId)->where('ends_at', '>', now())->max('ends_at');
        $startsAt = $latestEnd ? now()->parse($latestEnd) : now();

        Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->grantPlanId,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMonths($this->grantMonths),
        ]);

        session()->flash('status', 'Langganan ditambahkan secara manual.');
    }

    public function endSubscription(int $subscriptionId): void
    {
        $this->user->subscriptions()->whereKey($subscriptionId)->update(['ends_at' => now()]);
    }

    public function updateRole(): void
    {
        abort_if($this->user->is(auth()->user()), 403, 'Tidak bisa mengubah peran akun sendiri.');

        $this->validate(['role' => ['required', Rule::enum(UserRole::class)]]);
        $this->user->forceFill(['role' => $this->role])->save();
        session()->flash('status', 'Peran pengguna diperbarui.');
    }

    public function toggleBlock(): void
    {
        abort_if($this->user->is(auth()->user()), 403, 'Tidak bisa memblokir akun sendiri.');

        $this->user->forceFill(['blocked_at' => $this->user->isBlocked() ? null : now()])->save();
    }

    public function render(): View
    {
        return view('livewire.admin.users.show', [
            'subscriptions' => $this->user->subscriptions()->with('plan')->latest('ends_at')->get(),
            'attempts' => $this->user->examAttempts()->with('package')->latest('id')->take(10)->get(),
            'orders' => $this->user->orders()->with('plan')->latest()->take(10)->get(),
            'plans' => Plan::query()->orderBy('sort_order')->get(['id', 'name']),
            'roles' => UserRole::cases(),
        ])->title($this->user->name);
    }
}
