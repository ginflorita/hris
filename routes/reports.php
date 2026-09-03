<?php

use App\Http\Controllers\Admin\AnalyticsReportController;
use App\Http\Controllers\Admin\HrReportController;
use App\Http\Controllers\Admin\PayrollReportController;
use App\Http\Controllers\Admin\PerformanceReportController;
use App\Http\Controllers\Admin\RecruitmentReportController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\TrainingReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'mfa.superadmin'])
    ->prefix('admin/reports')->name('admin.reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('hr', [HrReportController::class, 'index'])->name('hr.index');
        Route::get('payroll', [PayrollReportController::class, 'index'])->name('payroll.index');
        Route::get('recruitment', [RecruitmentReportController::class, 'index'])->name('recruitment.index');
        Route::get('performance', [PerformanceReportController::class, 'index'])->name('performance.index');
        Route::get('training', [TrainingReportController::class, 'index'])->name('training.index');
        Route::get('analytics', [AnalyticsReportController::class, 'index'])->name('analytics.index');
    });
