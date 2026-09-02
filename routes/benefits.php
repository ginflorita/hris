<?php

use App\Http\Controllers\Admin\BenefitPlanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'mfa.superadmin'])
    ->prefix('admin/benefits')->name('admin.benefits.')->group(function () {
        Route::resource('plans', BenefitPlanController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    });
