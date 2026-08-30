<?php

namespace App\Listeners;

use App\Notifications\SecurityAlert;
use Laravel\Fortify\Events\PasswordUpdatedViaController;

class NotifyOnPasswordChanged
{
    public function handle(PasswordUpdatedViaController $event): void
    {
        $event->user->notify(new SecurityAlert('Your password was just changed.'));
    }
}
