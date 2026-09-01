<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeOnboarding;
use App\Models\EmployeeOnboardingTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeOnboardingTaskController extends Controller
{
    /**
     * A plain checkbox toggle -- checking it stamps completed_at/
     * completed_by, unchecking clears both so HR can correct a mis-click
     * without leaving a stale completion record behind.
     */
    public function update(Request $request, Employee $employee, EmployeeOnboarding $onboarding, EmployeeOnboardingTask $task): RedirectResponse
    {
        $this->authorize('employees.update');
        abort_unless($onboarding->employee_id === $employee->id, 404);
        abort_unless($task->employee_onboarding_id === $onboarding->id, 404);

        $isCompleted = $request->boolean('is_completed');

        $task->update([
            'is_completed' => $isCompleted,
            'completed_at' => $isCompleted ? now() : null,
            'completed_by' => $isCompleted ? $request->user()->id : null,
        ]);

        return back()->with('status', $isCompleted ? 'Task marked complete.' : 'Task marked incomplete.');
    }
}
