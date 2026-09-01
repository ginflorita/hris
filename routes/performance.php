<?php

use App\Http\Controllers\Admin\PerformanceCycleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'mfa.superadmin'])
    ->prefix('admin/performance')->name('admin.performance.')->group(function () {
        Route::resource('cycles', PerformanceCycleController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::put('cycles/{cycle}/activate', [PerformanceCycleController::class, 'activate'])->name('cycles.activate');
        Route::put('cycles/{cycle}/close', [PerformanceCycleController::class, 'close'])->name('cycles.close');
    });
