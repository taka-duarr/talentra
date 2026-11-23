<?php

namespace App\Livewire\Tryout;

use App\Models\ExamPackage;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class TryoutShow extends Component
{
    public ExamPackage $package;

    public function mount(ExamPackage $package): void
    {
        abort_unless($package->status->value === 'published' || auth()->user()->isAdmin(), 404);

        $this->package = $package->load(['category', 'sections' => fn ($query) => $query->withCount('questions')]);
    }

    public function render(): View
    {
        $user = auth()->user();
        $attempts = $this->package->attempts()->where('user_id', $user->id)->latest('id')->get();

        return view('livewire.tryout.show', [
            'unlocked' => $this->package->isAccessibleBy($user),
            'attempts' => $attempts,
            'running' => $attempts->first(fn ($attempt) => $attempt->isInProgress() && ! $attempt->isExpired()),
            'attemptsLeft' => $this->package->max_attempts === null ? null : max(0, $this->package->max_attempts - $attempts->count()),
            'questionCount' => $this->package->sections->sum('questions_count'),
        ])->title($this->package->title);
    }
}
