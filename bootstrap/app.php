<?php

use App\Http\Middleware\EnsureSuperadminHasTwoFactorEnabled;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            // Without this, Auth::logoutOtherDevices() rehashes the
            // password but nothing ever checks for the mismatch on other
            // sessions, so they'd silently keep working forever.
            'auth.session' => AuthenticateSession::class,
            'mfa.superadmin' => EnsureSuperadminHasTwoFactorEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
