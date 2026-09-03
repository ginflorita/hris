<?php

namespace App\Notifications;

use App\Models\TrainingEnrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrainingCertificateExpiring extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly TrainingEnrollment $enrollment,
        private readonly int $daysUntilExpiration,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your training certificate expires soon')
            ->line("Your certificate for \"{$this->enrollment->session->course->name}\" expires in {$this->daysUntilExpiration} day(s), on {$this->enrollment->certificate_expires_at->format('M d, Y')}.")
            ->line('Certificate number: '.$this->enrollment->certificate_number);
    }
}
