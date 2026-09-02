<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blueprint §51 17.14: security headers, tuned to this app's actual
 * frontend rather than a generic strict policy that would break it.
 *
 * script-src/style-src need 'unsafe-inline' because the app genuinely
 * relies on inline `<script>` (the pre-first-paint theme setter in
 * layouts/partials/head.blade.php, to avoid a flash of the wrong mode),
 * inline `onchange="this.form.submit()"` attributes (auto-submitting
 * filter dropdowns across the admin views), and inline `style="width:
 * ...%"` (progress bars). script-src also needs 'unsafe-eval' because
 * Alpine.js evaluates `x-data`/`@click` expressions via `new Function()`
 * internally — the CSP-safe `@alpinejs/csp` build only supports a
 * restricted expression syntax and would mean re-authoring every
 * existing directive, out of scope for this hardening pass. Removing
 * 'unsafe-inline'/'unsafe-eval' is a genuine follow-up (nonce the theme
 * script, convert onchange attributes to addEventListener, migrate to
 * the CSP Alpine build) — not done here so this policy doesn't claim
 * protection it doesn't actually provide.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            "frame-src 'none'",
        ]));

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', implode(', ', [
            'camera=()',
            'microphone=()',
            'geolocation=()',
            'payment=()',
            'usb=()',
        ]));

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
