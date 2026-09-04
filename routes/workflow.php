<?php

use App\Http\Controllers\Admin\WorkflowDefinitionController;
use App\Http\Controllers\Admin\WorkflowStepController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'mfa.superadmin'])
    ->prefix('admin/workflow')->name('admin.workflow.')->group(function () {
        Route::resource('definitions', WorkflowDefinitionController::class)
            ->parameters(['definitions' => 'workflow_definition']);
        Route::post('definitions/{workflow_definition}/steps', [WorkflowStepController::class, 'store'])
            ->name('definitions.steps.store');
        Route::put('definitions/{workflow_definition}/steps/{step}', [WorkflowStepController::class, 'update'])
            ->name('definitions.steps.update');
        Route::delete('definitions/{workflow_definition}/steps/{step}', [WorkflowStepController::class, 'destroy'])
            ->name('definitions.steps.destroy');
    });
