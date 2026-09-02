<?php

use App\Http\Controllers\Admin\OffboardingRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'mfa.superadmin'])
    ->prefix('admin/offboarding-requests')->name('admin.offboarding-requests.')->group(function () {
        Route::get('/', [OffboardingRequestController::class, 'index'])->name('index');
    });
