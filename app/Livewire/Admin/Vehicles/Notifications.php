<?php

namespace App\Livewire\Admin\Vehicles;

use Livewire\Component;
use Livewire\WithPagination;

class Notifications extends Component
{
    use WithPagination;

    public function mount(): void
    {
        $this->authorizeView();
    }

    public function markAsRead(string $notificationId): void
    {
        $this->authorizeView();
        auth()->user()->notifications()->where('data->category', 'vehicle')->whereKey($notificationId)->firstOrFail()->markAsRead();
    }

    private function authorizeView(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('vehicles.view'), 403);
    }

    public function render()
    {
        $this->authorizeView();
        $notifications = auth()->user()->notifications()->where('data->category', 'vehicle')->latest()->paginate(20);
        $notifications->getCollection()->transform(function ($notification) {
            $data = $notification->data;

            // Older Phase 5 notifications predate the structured metadata fields.
            if (empty($data['document_type']) && preg_match('/\Avehicle-document-expiry:\d+:(insurance|revenue_license):(\d{4}-\d{2}-\d{2}):(30|14|7|expired)\z/', (string) ($data['event_key'] ?? ''), $matches) === 1) {
                $data['document_type'] = $matches[1];
                $data['expiry_date'] = $matches[2];
                $data['alert_type'] = $matches[3];
                if (preg_match('/\AVehicle\s+(\S+)/', (string) ($data['message'] ?? ''), $registration) === 1) {
                    $data['registration_number'] = $registration[1];
                }
                $notification->data = $data;
            }

            return $notification;
        });

        return view('livewire.admin.vehicles.notifications', [
            'notifications' => $notifications,
        ])->layout('layouts.admin');
    }
}
