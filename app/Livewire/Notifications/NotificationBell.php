<?php

namespace App\Livewire\Notifications;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class NotificationBell extends Component
{
    public function render(): View
    {
        return view('livewire.notifications.bell', [
            'unread' => auth()->user()->unreadNotifications()->count(),
        ]);
    }
}
