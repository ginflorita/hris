<?php

use App\Http\Controllers\Admin\CompetencyController;
use App\Http\Controllers\Admin\SkillController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'mfa.superadmin'])
    ->prefix('admin/training')->name('admin.training.')->group(function () {
        Route::resource('competencies', CompetencyController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::resource('skills', SkillController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    });
