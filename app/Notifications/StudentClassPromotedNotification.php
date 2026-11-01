<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentClassPromotedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->greeting('Halo ' . ($notifiable->name ?? ''))
            ->line($this->message)
            ->line('Silakan masuk ke aplikasi QSEE untuk melihat informasi lebih lanjut.');
    }
}
