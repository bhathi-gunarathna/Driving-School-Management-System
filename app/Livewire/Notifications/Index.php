<?php

namespace App\Livewire\Notifications;

use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public function mount(): void { $this->authorizeRecipient(); }

    private function authorizeRecipient(): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->hasRole('Owner') || ($user->hasRole('Coach') && $user->is_active)), 403);
    }

    public function markAsRead(string $notificationId): void
    {
        $this->authorizeRecipient();
        auth()->user()->notifications()->whereKey($notificationId)->firstOrFail()->markAsRead();
    }

    public function markAllAsRead(): void
    {
        $this->authorizeRecipient();
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
    }

    public function render()
    {
        $this->authorizeRecipient();
        $layout = auth()->user()->hasRole('Owner') ? 'layouts.admin' : 'layouts.coach';
        return view('livewire.notifications.index', [
            'notifications' => auth()->user()->notifications()->latest()->paginate(20),
        ])->layout($layout);
    }
}
