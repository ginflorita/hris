<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CareerDevelopmentPlanStatus;
use App\Http\Controllers\Controller;
use App\Models\CareerDevelopmentPlan;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * A per-employee record, same nested-controller-plus-modal shape as
 * PerformanceImprovementPlan, gated by performance.manage -- Talent
 * Management's existing permission group, reused since neither Career
 * Development nor Succession Planning have one of their own in the
 * seeded catalog and blueprint gives no detail suggesting either needs
 * a dedicated group. Active until achieve()/cancel() moves it to a
 * terminal state; update()/destroy() only work while Active, the same
 * "don't rewrite a settled record" guard PIP's own lifecycle uses.
 */
class EmployeeCareerDevelopmentPlanController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('performance.manage');

        $employee->careerDevelopmentPlans()->create([
            ...$this->validated($request, $employee),
            'status' => CareerDevelopmentPlanStatus::Active,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Career development plan added.');
    }

    public function update(Request $request, Employee $employee, CareerDevelopmentPlan $plan): RedirectResponse
    {
        $this->authorize('performance.manage');
        abort_unless($plan->employee_id === $employee->id, 404);
        abort_unless($plan->status === CareerDevelopmentPlanStatus::Active, 422, 'Only an active plan can be edited.');

        $plan->update($this->validated($request, $employee));

        return back()->with('status', 'Career development plan updated.');
    }

    public function achieve(Employee $employee, CareerDevelopmentPlan $plan): RedirectResponse
    {
        $this->authorize('performance.manage');
        abort_unless($plan->employee_id === $employee->id, 404);
        abort_unless($plan->status === CareerDevelopmentPlanStatus::Active, 422, 'Only an active plan can be marked achieved.');

        $plan->update(['status' => CareerDevelopmentPlanStatus::Achieved]);

        return back()->with('status', 'Career development plan marked achieved.');
    }

    public function cancel(Employee $employee, CareerDevelopmentPlan $plan): RedirectResponse
    {
        $this->authorize('performance.manage');
        abort_unless($plan->employee_id === $employee->id, 404);
        abort_unless($plan->status === CareerDevelopmentPlanStatus::Active, 422, 'Only an active plan can be cancelled.');

        $plan->update(['status' => CareerDevelopmentPlanStatus::Cancelled]);

        return back()->with('status', 'Career development plan cancelled.');
    }

    public function destroy(Employee $employee, CareerDevelopmentPlan $plan): RedirectResponse
    {
        $this->authorize('performance.manage');
        abort_unless($plan->employee_id === $employee->id, 404);
        abort_unless($plan->status === CareerDevelopmentPlanStatus::Active, 422, 'Only an active plan can be removed.');

        $plan->delete();

        return back()->with('status', 'Career development plan removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Employee $employee): array
    {
        return $request->validate([
            'target_position_id' => ['nullable', Rule::exists('positions', 'id')->where('company_id', $employee->company_id)],
            'target_date' => ['nullable', 'date'],
            'development_actions' => ['required', 'string', 'max:4000'],
        ]);
    }
}
