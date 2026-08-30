<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($session) => (object) [
                'id' => $session->id,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_active' => Carbon::createFromTimestamp($session->last_activity),
                'is_current_device' => $session->id === $request->session()->getId(),
            ]);

        $twoFactorPending = ! is_null($user->two_factor_secret) && is_null($user->two_factor_confirmed_at);

        return view('security.index', [
            'sessions' => $sessions,
            'twoFactorEnabled' => $user->hasEnabledTwoFactorAuthentication(),
            'twoFactorPending' => $twoFactorPending,
            'qrCodeSvg' => $twoFactorPending ? $user->twoFactorQrCodeSvg() : null,
            'recoveryCodes' => $user->hasEnabledTwoFactorAuthentication() ? $user->recoveryCodes() : null,
        ]);
    }

    public function logoutOtherDevices(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        Auth::guard('web')->logoutOtherDevices($request->password);

        // logoutOtherDevices() only invalidates other sessions on their next
        // request (via the auth.session middleware); prune the rows here so
        // the list reflects the change immediately.
        DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return back()->with('status', 'Logged out of all other browser sessions.');
    }

    public function destroySession(Request $request, string $sessionId): RedirectResponse
    {
        if ($sessionId === $request->session()->getId()) {
            return back()->withErrors(['session' => "That's your current session — use Log out instead."]);
        }

        DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', $sessionId)
            ->delete();

        return back()->with('status', 'Session logged out.');
    }
}
