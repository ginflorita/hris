<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PerformanceImprovementPlanStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PerformanceImprovementPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * A PIP (blueprint §22) is a per-employee record, same nested-controller-
 * plus-modal shape as Goals/Reviews, gated by the same performance.manage
 * permission. It's forward-looking (a bounded improvement period with a
 * closing outcome), not a review, so it gets its own lifecycle: Active
 * until close() moves it to Successful/Unsuccessful/Cancelled -- update()/
 * destroy() are only allowed while Active, the same "don't rewrite a
 * closed record" rule PerformanceReview's Draft-only edit uses.
 */
class EmployeePerformanceImprovementPlanController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('performance.manage');

        $employee->performanceImprovementPlans()->create([
            ...$this->validated($request, $employee),
            'status' => PerformanceImprovementPlanStatus::Active,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Performance improvement plan added.');
    }

    public function update(Request $request, Employee $employee, PerformanceImprovementPlan $plan): RedirectResponse
    {
        $this->authorize('performance.manage');
        abort_unless($plan->employee_id === $employee->id, 404);
        abort_unless($plan->status === PerformanceImprovementPlanStatus::Active, 422, 'Only an active plan can be edited.');

        $plan->update($this->validated($request, $employee, $plan));

        return back()->with('status', 'Performance improvement plan updated.');
    }

    public function close(Request $request, Employee $employee, PerformanceImprovementPlan $plan): RedirectResponse
    {
        $this->authorize('performance.manage');
        abort_unless($plan->employee_id === $employee->id, 404);
        abort_unless($plan->status === PerformanceImprovementPlanStatus::Active, 422, 'Only an active plan can be closed.');

        $validated = $request->validate([
            'status' => ['required', Rule::in([
                PerformanceImprovementPlanStatus::Successful->value,
                PerformanceImprovementPlanStatus::Unsuccessful->value,
                PerformanceImprovementPlanStatus::Cancelled->value,
            ])],
            'outcome_notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $plan->update([
            ...$validated,
            'closed_at' => now(),
            'closed_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Performance improvement plan closed.');
    }

    public function destroy(Employee $employee, PerformanceImprovementPlan $plan): RedirectResponse
    {
        $this->authorize('performance.manage');
        abort_unless($plan->employee_id === $employee->id, 404);
        abort_unless($plan->status === PerformanceImprovementPlanStatus::Active, 422, 'Only an active plan can be removed.');

        $plan->delete();

        return back()->with('status', 'Performance improvement plan removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Employee $employee, ?PerformanceImprovementPlan $plan = null): array
    {
        return $request->validate([
            'performance_review_id' => ['nullable', Rule::exists('performance_reviews', 'id')->where('employee_id', $employee->id)],
            'initiated_by' => ['required', Rule::exists('employees', 'id')->where('company_id', $employee->company_id)],
            'reason' => ['required', 'string', 'max:4000'],
            'goals' => ['required', 'string', 'max:4000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);
    }
}
