<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Notifications\StudentClassPromotedEmailNotification;

class StudentClassPromotedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public bool $databaseOnly = false,
        public ?string $url = null,
        public string $actionLabel = 'Lihat detail',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        if (! $this->databaseOnly && filled($notifiable->email)) {
            $notifiable->notify(new StudentClassPromotedEmailNotification($this->title, $this->message));
        }

        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'action_label' => $this->actionLabel,
        ];
    }
}
