<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SuccessionReadiness;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SuccessionCandidate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * No lifecycle here, unlike CareerDevelopmentPlan -- a succession
 * candidacy is just "is this employee currently a candidate for this
 * position, and how ready," which changes by editing readiness/notes
 * in place or removing the row, not by moving through a terminal state.
 * Gated by performance.manage, same reuse reasoning as
 * EmployeeCareerDevelopmentPlanController.
 */
class EmployeeSuccessionCandidateController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('performance.manage');

        $employee->successionCandidacies()->create([
            ...$this->validated($request, $employee),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Succession candidacy added.');
    }

    public function update(Request $request, Employee $employee, SuccessionCandidate $candidate): RedirectResponse
    {
        $this->authorize('performance.manage');
        abort_unless($candidate->employee_id === $employee->id, 404);

        $candidate->update($this->validated($request, $employee, $candidate));

        return back()->with('status', 'Succession candidacy updated.');
    }

    public function destroy(Employee $employee, SuccessionCandidate $candidate): RedirectResponse
    {
        $this->authorize('performance.manage');
        abort_unless($candidate->employee_id === $employee->id, 404);

        $candidate->delete();

        return back()->with('status', 'Succession candidacy removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Employee $employee, ?SuccessionCandidate $candidate = null): array
    {
        return $request->validate([
            'position_id' => [
                'required',
                Rule::exists('positions', 'id')->where('company_id', $employee->company_id),
                Rule::unique('succession_candidates')->where('employee_id', $employee->id)->ignore($candidate?->id),
            ],
            'readiness' => ['required', Rule::enum(SuccessionReadiness::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
