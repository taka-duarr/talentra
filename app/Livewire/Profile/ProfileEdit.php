<?php

namespace App\Livewire\Profile;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Profil & pengaturan')]
class ProfileEdit extends Component
{
    public string $name = '';

    public string $display_name = '';

    public ?int $target_category_id = null;

    public bool $hide_from_ranking = false;

    public bool $email_notifications = true;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->display_name = (string) $user->display_name;
        $this->target_category_id = $user->target_category_id;
        $this->hide_from_ranking = $user->hide_from_ranking;
        $this->email_notifications = $user->email_notifications;
    }

    public function saveProfile(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:40'],
            'target_category_id' => ['nullable', Rule::exists(Category::class, 'id')],
            'hide_from_ranking' => ['boolean'],
            'email_notifications' => ['boolean'],
        ]);

        auth()->user()->update([
            ...$validated,
            'display_name' => $validated['display_name'] ?: null,
        ]);

        session()->flash('status', 'Profil berhasil disimpan.');
        $this->redirectRoute('profile.edit', navigate: true);
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        auth()->user()->update(['password' => Hash::make($this->password)]);

        $this->reset('current_password', 'password', 'password_confirmation');
        session()->flash('status', 'Password berhasil diubah.');
        $this->redirectRoute('profile.edit', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.profile.edit', [
            'categories' => Category::query()->active()->ordered()->get(['id', 'name']),
        ]);
    }
}
