<?php

use App\Http\Controllers\Admin\LeaveCalendarController;
use App\Http\Controllers\Admin\LeavePolicyController;
use App\Http\Controllers\Admin\LeaveReportController;
use App\Http\Controllers\Admin\LeaveRequestController;
use App\Http\Controllers\Admin\LeaveTypeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'mfa.superadmin'])
    ->prefix('admin/leave')->name('admin.leave.')->group(function () {
        Route::resource('types', LeaveTypeController::class)->except('show');
        Route::resource('policies', LeavePolicyController::class)->except('show');
        Route::resource('requests', LeaveRequestController::class)->except(['show', 'edit', 'update', 'destroy']);
        Route::put('requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('requests.approve');
        Route::put('requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('requests.reject');
        Route::put('requests/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel'])->name('requests.cancel');

        Route::get('calendar', [LeaveCalendarController::class, 'index'])->name('calendar.index');
        Route::get('report', [LeaveReportController::class, 'index'])->name('report.index');
    });
