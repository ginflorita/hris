<?php

namespace App\Listeners;

use App\Notifications\SecurityAlert;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;

class NotifyOnTwoFactorDisabled
{
    public function handle(TwoFactorAuthenticationDisabled $event): void
    {
        $event->user->notify(new SecurityAlert('Two-factor authentication was disabled on your account.'));
    }
}
