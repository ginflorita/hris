<?php

use App\Http\Controllers\Admin\CompetencyController;
use App\Http\Controllers\Admin\SkillController;
use App\Http\Controllers\Admin\TrainingCourseController;
use App\Http\Controllers\Admin\TrainingEnrollmentController;
use App\Http\Controllers\Admin\TrainingProviderController;
use App\Http\Controllers\Admin\TrainingSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'mfa.superadmin'])
    ->prefix('admin/training')->name('admin.training.')->group(function () {
        Route::resource('competencies', CompetencyController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::resource('skills', SkillController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::resource('providers', TrainingProviderController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::resource('courses', TrainingCourseController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

        Route::prefix('courses/{course}')->name('courses.')->group(function () {
            Route::post('sessions', [TrainingSessionController::class, 'store'])->name('sessions.store');
            Route::get('sessions/{session}', [TrainingSessionController::class, 'show'])->name('sessions.show');
            Route::put('sessions/{session}', [TrainingSessionController::class, 'update'])->name('sessions.update');
            Route::put('sessions/{session}/complete', [TrainingSessionController::class, 'complete'])->name('sessions.complete');
            Route::put('sessions/{session}/cancel', [TrainingSessionController::class, 'cancel'])->name('sessions.cancel');
            Route::delete('sessions/{session}', [TrainingSessionController::class, 'destroy'])->name('sessions.destroy');

            Route::post('sessions/{session}/enrollments', [TrainingEnrollmentController::class, 'store'])->name('sessions.enrollments.store');
            Route::put('sessions/{session}/enrollments/{enrollment}', [TrainingEnrollmentController::class, 'update'])->name('sessions.enrollments.update');
            Route::delete('sessions/{session}/enrollments/{enrollment}', [TrainingEnrollmentController::class, 'destroy'])->name('sessions.enrollments.destroy');
        });
    });
