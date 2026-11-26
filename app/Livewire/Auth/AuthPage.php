<?php

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Shared sign-in / sign-up card. Both forms live on the page so the
 * decorative panel can sweep between them without a page load.
 */
#[Layout('layouts::auth')]
class AuthPage extends Component
{
    public string $mode = 'login';

    public function mount(): void
    {
        $this->mode = request()->routeIs('register') ? 'register' : 'login';
    }

    public function render(): View
    {
        return view('livewire.auth.auth-page')
            ->title($this->mode === 'register' ? 'Daftar' : 'Masuk');
    }
}
