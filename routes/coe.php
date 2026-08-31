<?php

use App\Http\Controllers\Admin\CoeRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'mfa.superadmin'])
    ->prefix('admin/coe-requests')->name('admin.coe-requests.')->group(function () {
        Route::get('/', [CoeRequestController::class, 'index'])->name('index');
        Route::put('{coe_request}/approve', [CoeRequestController::class, 'approve'])->name('approve');
        Route::put('{coe_request}/reject', [CoeRequestController::class, 'reject'])->name('reject');
        Route::get('{coe_request}/download', [CoeRequestController::class, 'download'])->name('download');
    });
