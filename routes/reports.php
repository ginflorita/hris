<?php

use App\Http\Controllers\Admin\HrReportController;
use App\Http\Controllers\Admin\PayrollReportController;
use App\Http\Controllers\Admin\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'mfa.superadmin'])
    ->prefix('admin/reports')->name('admin.reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('hr', [HrReportController::class, 'index'])->name('hr.index');
        Route::get('payroll', [PayrollReportController::class, 'index'])->name('payroll.index');
    });
