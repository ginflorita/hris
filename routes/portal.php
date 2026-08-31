<?php

use App\Http\Controllers\Portal\AttendanceController;
use App\Http\Controllers\Portal\CoeRequestController;
use App\Http\Controllers\Portal\LeaveController;
use App\Http\Controllers\Portal\OvertimeController;
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

        Route::get('leave', [LeaveController::class, 'index'])->name('leave.index');
        Route::get('leave/create', [LeaveController::class, 'create'])->name('leave.create');
        Route::post('leave', [LeaveController::class, 'store'])->name('leave.store');
        Route::put('leave/{leave_request}/cancel', [LeaveController::class, 'cancel'])->name('leave.cancel');

        Route::get('overtime', [OvertimeController::class, 'index'])->name('overtime.index');
        Route::post('overtime', [OvertimeController::class, 'store'])->name('overtime.store');

        Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('attendance/{attendance}/correction-requests', [AttendanceController::class, 'store'])->name('attendance.correction-requests.store');

        Route::get('coe', [CoeRequestController::class, 'index'])->name('coe.index');
        Route::post('coe', [CoeRequestController::class, 'store'])->name('coe.store');
        Route::get('coe/{coe_request}/download', [CoeRequestController::class, 'download'])->name('coe.download');
    });
