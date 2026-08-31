<?php

use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\AttendanceCorrectionRequestController;
use App\Http\Controllers\Admin\AttendanceReportController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\OvertimeRequestController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\ShiftController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'mfa.superadmin'])
    ->prefix('admin/attendance')->name('admin.attendance.')->group(function () {
        Route::resource('holidays', HolidayController::class)->except('show');
        Route::resource('shifts', ShiftController::class)->except('show');
        Route::resource('schedules', ScheduleController::class)->except('show');
        Route::resource('attendances', AttendanceController::class)->except(['show', 'destroy']);

        Route::resource('overtime', OvertimeRequestController::class)->except(['show', 'edit', 'update', 'destroy']);
        Route::put('overtime/{overtimeRequest}/approve', [OvertimeRequestController::class, 'approve'])->name('overtime.approve');
        Route::put('overtime/{overtimeRequest}/reject', [OvertimeRequestController::class, 'reject'])->name('overtime.reject');

        Route::get('correction-requests', [AttendanceCorrectionRequestController::class, 'index'])->name('correction-requests.index');
        Route::put('correction-requests/{correction_request}/approve', [AttendanceCorrectionRequestController::class, 'approve'])
            ->name('correction-requests.approve');
        Route::put('correction-requests/{correction_request}/reject', [AttendanceCorrectionRequestController::class, 'reject'])
            ->name('correction-requests.reject');

        Route::get('report', [AttendanceReportController::class, 'index'])->name('report.index');
    });
