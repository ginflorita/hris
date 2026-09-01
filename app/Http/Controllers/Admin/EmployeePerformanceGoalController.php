<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PerformanceGoalStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PerformanceGoal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * A per-employee record, same nested-controller-plus-modal shape as
 * CompensationItem -- but gated by performance.view/performance.manage,
 * since Performance has its own seeded permission group (unlike
 * Compensation, which had to borrow employees.view/employees.update).
 */
class EmployeePerformanceGoalController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('performance.manage');

        $employee->performanceGoals()->create([
            ...$this->validated($request, $employee),
            'status' => PerformanceGoalStatus::NotStarted,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Goal added.');
    }

    public function update(Request $request, Employee $employee, PerformanceGoal $goal): RedirectResponse
    {
        $this->authorize('performance.manage');
        abort_unless($goal->employee_id === $employee->id, 404);

        $validated = [
            ...$this->validated($request, $employee),
            'status' => $request->validate(['status' => ['required', Rule::enum(PerformanceGoalStatus::class)]])['status'],
        ];

        $goal->update($validated);

        return back()->with('status', 'Goal updated.');
    }

    public function destroy(Employee $employee, PerformanceGoal $goal): RedirectResponse
    {
        $this->authorize('performance.manage');
        abort_unless($goal->employee_id === $employee->id, 404);

        $goal->delete();

        return back()->with('status', 'Goal removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Employee $employee): array
    {
        return $request->validate([
            'performance_cycle_id' => ['required', Rule::exists('performance_cycles', 'id')->where('company_id', $employee->company_id)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'target_date' => ['nullable', 'date'],
            'weight' => ['nullable', 'integer', 'min:1', 'max:100'],
            'target_value' => ['nullable', 'numeric'],
            'actual_value' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string', 'max:50'],
        ]);
    }
}
