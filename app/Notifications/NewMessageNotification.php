<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $senderName,
        private readonly string $messageBody,
        private readonly int $messageId
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $preview = mb_strimwidth($this->messageBody, 0, 90, '…');

        return [
            'title' => 'New message from '.$this->senderName,
            'message' => $preview,
            'url' => route('messages.show', $this->messageId),
            'request_type' => 'new_message',
            'message_id' => $this->messageId,
        ];
    }
}
