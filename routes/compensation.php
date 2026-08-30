<?php

use App\Http\Controllers\Admin\SalaryGradeController;
use App\Http\Controllers\Admin\SalaryStructureController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'mfa.superadmin'])
    ->prefix('admin/compensation')->name('admin.compensation.')->group(function () {
        Route::resource('structures', SalaryStructureController::class)->except('show');
        Route::resource('grades', SalaryGradeController::class)->except('show');
    });
