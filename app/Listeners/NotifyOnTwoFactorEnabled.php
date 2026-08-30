<?php

namespace App\Listeners;

use App\Notifications\SecurityAlert;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;

class NotifyOnTwoFactorEnabled
{
    public function handle(TwoFactorAuthenticationConfirmed $event): void
    {
        $event->user->notify(new SecurityAlert('Two-factor authentication was enabled on your account.'));
    }
}
