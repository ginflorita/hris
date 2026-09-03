<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'mfa.superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    Route::resource('users', UserController::class)->except('show', 'destroy');

    Route::prefix('users/{user}')->name('users.')->group(function () {
        Route::put('/roles', [UserController::class, 'updateRoles'])->name('roles.update');
        Route::post('/disable', [UserController::class, 'disable'])->name('disable');
        Route::post('/enable', [UserController::class, 'enable'])->name('enable');
        Route::post('/force-logout', [UserController::class, 'forceLogout'])->name('force-logout');
        Route::post('/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
    });

    Route::resource('roles', RoleController::class)->except('show');
    Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
});
