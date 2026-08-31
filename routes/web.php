<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'mfa.superadmin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/organization.php';
require __DIR__.'/employees.php';
require __DIR__.'/attendance.php';
require __DIR__.'/leave.php';
require __DIR__.'/compensation.php';
require __DIR__.'/payroll.php';
