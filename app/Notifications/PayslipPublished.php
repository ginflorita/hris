<?php

namespace App\Notifications;

use App\Models\PayrollItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayslipPublished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly PayrollItem $payrollItem) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your payslip is ready: '.$this->payrollItem->payrollPeriod->name)
            ->line('Your payslip for '.$this->payrollItem->payrollPeriod->name.' is now available.')
            ->action('View payslip', route('portal.payslips.show', $this->payrollItem))
            ->line('Net pay: '.number_format((float) $this->payrollItem->net_pay, 2));
    }
}
