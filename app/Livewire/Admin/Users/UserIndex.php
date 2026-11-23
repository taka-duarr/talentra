<?php

namespace App\Livewire\Admin\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin')]
#[Title('Pengguna')]
class UserIndex extends Component
{
    use WithPagination;

    #[Url(as: 'cari')]
    public string $search = '';

    #[Url]
    public string $role = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $users = User::query()
            ->with('targetCategory')
            ->withCount(['examAttempts', 'subscriptions' => fn ($query) => $query->active()])
            ->when($this->search !== '', fn ($query) => $query->where(fn ($inner) => $inner->where('name', 'like', '%'.$this->search.'%')->orWhere('email', 'like', '%'.$this->search.'%')))
            ->when($this->role !== '', fn ($query) => $query->where('role', $this->role))
            ->latest()
            ->paginate(25);

        return view('livewire.admin.users.index', [
            'users' => $users,
            'roles' => UserRole::cases(),
        ]);
    }
}
