<?php

use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\CostCenterController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DivisionController;
use App\Http\Controllers\Admin\JobGradeController;
use App\Http\Controllers\Admin\JobLevelController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\TeamController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'mfa.superadmin'])
    ->prefix('admin/organization')->name('admin.organization.')->group(function () {
        Route::resource('companies', CompanyController::class)->except('show');
        Route::resource('branches', BranchController::class)->except('show');
        Route::resource('divisions', DivisionController::class)->except('show');
        Route::resource('departments', DepartmentController::class)->except('show');
        Route::resource('sections', SectionController::class)->except('show');
        Route::resource('teams', TeamController::class)->except('show');
        Route::resource('job-levels', JobLevelController::class)->except('show');
        Route::resource('job-grades', JobGradeController::class)->except('show');
        Route::resource('cost-centers', CostCenterController::class)->except('show');
        Route::resource('positions', PositionController::class)->except('show');
    });
