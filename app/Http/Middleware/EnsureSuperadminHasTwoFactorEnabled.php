<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blueprint §17.2/§30: MFA is mandatory for Superadmin. Every other role
 * can leave two-factor authentication off; a user holding the Superadmin
 * role is redirected to set it up before doing anything else.
 */
class EnsureSuperadminHasTwoFactorEnabled
{
    /**
     * Route names a Superadmin without 2FA yet must still be able to
     * reach: the security page itself, the 2FA setup/confirm endpoints
     * (which in turn require password confirmation), and logout.
     */
    private const ALLOWED_ROUTES = [
        'security.*',
        'two-factor.*',
        'password.confirm',
        'password.confirm.store',
        'password.confirmation',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isSuperadmin() || $user->hasEnabledTwoFactorAuthentication()) {
            return $next($request);
        }

        if ($request->routeIs(...self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        return redirect()->route('security.index')
            ->with('warning', 'Two-factor authentication is required for Superadmin accounts — set it up below to continue.');
    }
}
