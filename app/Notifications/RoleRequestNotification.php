<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RoleRequestNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly array $payload)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->payload['title'] ?? 'New request',
            'message' => $this->payload['message'] ?? 'A new request needs your attention.',
            'url' => $this->payload['url'] ?? url('/'),
            'request_type' => $this->payload['request_type'] ?? 'general',
            'created_by' => $this->payload['created_by'] ?? null,
            'subject_id' => $this->payload['subject_id'] ?? null,
        ];
    }
}
