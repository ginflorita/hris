<?php

use App\Http\Controllers\Portal\PayslipController;
use App\Http\Controllers\Portal\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'mfa.superadmin'])
    ->prefix('portal')->name('portal.')->group(function () {
        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::get('profile/documents/{document}', [ProfileController::class, 'downloadDocument'])->name('profile.documents.download');

        Route::get('payslips', [PayslipController::class, 'index'])->name('payslips.index');
        Route::get('payslips/{payroll_item}', [PayslipController::class, 'show'])->name('payslips.show');
        Route::get('payslips/{payroll_item}/download', [PayslipController::class, 'download'])->name('payslips.download');
    });
