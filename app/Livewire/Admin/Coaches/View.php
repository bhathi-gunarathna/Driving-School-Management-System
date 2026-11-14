<?php

namespace App\Livewire\Admin\Coaches;

use App\Models\User;
use Livewire\Component;

class View extends Component
{
    public User $coach;

    public function mount(User $coach): void
    {
        $user = auth()->user();

        abort_unless($user?->hasRole('Owner') && $user->can('coaches.view'), 403);

        $this->coach = User::role('Coach')->findOrFail($coach->getKey());
    }

    public function render()
    {
        return view('livewire.admin.coaches.view')
            ->layout('layouts.admin');
    }
}
