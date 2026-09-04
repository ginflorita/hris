<?php

use App\Http\Controllers\Admin\WorkflowDefinitionController;
use App\Http\Controllers\Admin\WorkflowInstanceController;
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

        // Explicit ->parameters() override, same as the resource above --
        // Route::resource('instances', ...) would otherwise wildcard as
        // {instance}, not matching this controller's $workflowInstance
        // parameter (see CLAUDE.md "Workflow" 20a for the bug this
        // already caused once).
        Route::resource('instances', WorkflowInstanceController::class)
            ->only(['index', 'show'])
            ->parameters(['instances' => 'workflow_instance']);
        Route::put('instances/{workflow_instance}/approve', [WorkflowInstanceController::class, 'approve'])
            ->name('instances.approve');
        Route::put('instances/{workflow_instance}/reject', [WorkflowInstanceController::class, 'reject'])
            ->name('instances.reject');
    });
