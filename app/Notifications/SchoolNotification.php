<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SchoolNotification extends Notification
{
    use Queueable;

    public function __construct(public string $eventKey, public string $title, public string $message, public string $url, public string $category) {}

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array
    {
        return ['event_key' => $this->eventKey, 'title' => $this->title, 'message' => $this->message, 'url' => $this->url, 'category' => $this->category];
    }
}
