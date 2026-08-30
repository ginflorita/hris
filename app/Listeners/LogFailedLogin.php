<?php

namespace App\Listeners;

use App\Enums\LoginLogEvent;
use App\Models\LoginLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;

class LogFailedLogin
{
    public function __construct(private Request $request) {}

    public function handle(Failed $event): void
    {
        // $event->credentials may carry the attempted password — never
        // persist anything from it besides the username field.
        LoginLog::create([
            'user_id' => $event->user?->getAuthIdentifier(),
            'email' => (string) ($event->credentials[Fortify::username()] ?? ''),
            'event' => LoginLogEvent::FailedLogin,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);
    }
}
