<?php

namespace App\Listeners;

use App\Enums\LoginLogEvent;
use App\Models\LoginLog;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

class LogLogout
{
    public function __construct(private Request $request) {}

    public function handle(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        LoginLog::create([
            'user_id' => $event->user->getAuthIdentifier(),
            'email' => $event->user->email,
            'event' => LoginLogEvent::Logout,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);
    }
}
