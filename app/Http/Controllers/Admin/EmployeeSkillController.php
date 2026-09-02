<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProficiencyLevel;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeSkill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeSkillController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('training.manage');

        $employee->employeeSkills()->create($this->validated($request, $employee));

        return back()->with('status', 'Skill rating added.');
    }

    public function update(Request $request, Employee $employee, EmployeeSkill $employeeSkill): RedirectResponse
    {
        $this->authorize('training.manage');
        abort_unless($employeeSkill->employee_id === $employee->id, 404);

        $employeeSkill->update($this->validated($request, $employee, $employeeSkill));

        return back()->with('status', 'Skill rating updated.');
    }

    public function destroy(Employee $employee, EmployeeSkill $employeeSkill): RedirectResponse
    {
        $this->authorize('training.manage');
        abort_unless($employeeSkill->employee_id === $employee->id, 404);

        $employeeSkill->delete();

        return back()->with('status', 'Skill rating removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Employee $employee, ?EmployeeSkill $employeeSkill = null): array
    {
        return $request->validate([
            'skill_id' => [
                'required',
                Rule::exists('skills', 'id')->where('company_id', $employee->company_id),
                Rule::unique('employee_skills')->where('employee_id', $employee->id)->ignore($employeeSkill?->id),
            ],
            'proficiency_level' => ['required', Rule::enum(ProficiencyLevel::class)],
            'assessed_at' => ['nullable', 'date'],
            'assessed_by' => ['nullable', Rule::exists('employees', 'id')->where('company_id', $employee->company_id)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
