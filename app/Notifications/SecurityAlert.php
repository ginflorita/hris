<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SecurityAlert extends Notification
{
    use Queueable;

    public function __construct(private readonly string $line) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Security alert: '.config('app.name'))
            ->line($this->line)
            ->line('If this wasn\'t you, contact your administrator immediately.');
    }
}
