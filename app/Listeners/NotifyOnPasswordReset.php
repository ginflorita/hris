<?php

namespace App\Listeners;

use App\Notifications\SecurityAlert;
use Illuminate\Auth\Events\PasswordReset;

class NotifyOnPasswordReset
{
    public function handle(PasswordReset $event): void
    {
        $event->user->notify(new SecurityAlert('Your password was just reset.'));
    }
}
