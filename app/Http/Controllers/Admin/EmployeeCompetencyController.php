<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProficiencyLevel;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeCompetency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * A per-employee record, same nested-controller-plus-modal shape as
 * PerformanceGoal, gated by training.manage since Competency/Skill
 * catalog data reuses Training's permission group (see CompetencyController).
 * `Rule::unique('employee_competencies')` turns the table's own
 * (employee_id, competency_id) unique index into a friendly validation
 * error instead of a raw QueryException -- the exact bug class CLAUDE.md's
 * Attendance section documents catching once already for Holiday's date
 * uniqueness, applied proactively here.
 */
class EmployeeCompetencyController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('training.manage');

        $employee->employeeCompetencies()->create($this->validated($request, $employee));

        return back()->with('status', 'Competency rating added.');
    }

    public function update(Request $request, Employee $employee, EmployeeCompetency $employeeCompetency): RedirectResponse
    {
        $this->authorize('training.manage');
        abort_unless($employeeCompetency->employee_id === $employee->id, 404);

        $employeeCompetency->update($this->validated($request, $employee, $employeeCompetency));

        return back()->with('status', 'Competency rating updated.');
    }

    public function destroy(Employee $employee, EmployeeCompetency $employeeCompetency): RedirectResponse
    {
        $this->authorize('training.manage');
        abort_unless($employeeCompetency->employee_id === $employee->id, 404);

        $employeeCompetency->delete();

        return back()->with('status', 'Competency rating removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Employee $employee, ?EmployeeCompetency $employeeCompetency = null): array
    {
        return $request->validate([
            'competency_id' => [
                'required',
                Rule::exists('competencies', 'id')->where('company_id', $employee->company_id),
                Rule::unique('employee_competencies')->where('employee_id', $employee->id)->ignore($employeeCompetency?->id),
            ],
            'proficiency_level' => ['required', Rule::enum(ProficiencyLevel::class)],
            'assessed_at' => ['nullable', 'date'],
            'assessed_by' => ['nullable', Rule::exists('employees', 'id')->where('company_id', $employee->company_id)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
