<?php

use App\Http\Controllers\SecurityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'mfa.superadmin'])->prefix('security')->name('security.')->group(function () {
    Route::get('/', [SecurityController::class, 'index'])->name('index');

    // Re-validates the current password inline, so it doesn't need the
    // password.confirm interstitial on top.
    Route::post('/sessions/logout-other', [SecurityController::class, 'logoutOtherDevices'])
        ->name('sessions.logout-other');

    Route::middleware('password.confirm')->group(function () {
        Route::delete('/sessions/{sessionId}', [SecurityController::class, 'destroySession'])
            ->name('sessions.destroy');
    });
});
