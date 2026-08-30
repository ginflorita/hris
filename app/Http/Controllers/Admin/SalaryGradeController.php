<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SalaryGrade;
use App\Models\SalaryStructure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SalaryGradeController extends Controller
{
    public function index(): View
    {
        $this->authorize('organization.view');

        return view('admin.compensation.grades.index', [
            'salaryGrades' => SalaryGrade::with(['company', 'salaryStructure'])->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('organization.manage');

        return view('admin.compensation.grades.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('organization.manage');

        SalaryGrade::create($this->validated($request));

        return redirect()->route('admin.compensation.grades.index')->with('status', 'Salary grade created.');
    }

    public function edit(SalaryGrade $grade): View
    {
        $this->authorize('organization.manage');

        return view('admin.compensation.grades.edit', ['salaryGrade' => $grade, ...$this->formData()]);
    }

    public function update(Request $request, SalaryGrade $grade): RedirectResponse
    {
        $this->authorize('organization.manage');

        $grade->update($this->validated($request, $grade));

        return redirect()->route('admin.compensation.grades.index')->with('status', 'Salary grade updated.');
    }

    public function destroy(SalaryGrade $grade): RedirectResponse
    {
        $this->authorize('organization.manage');

        if ($grade->employments()->exists()) {
            return back()->withErrors(['salaryGrade' => 'Reassign the employments using this salary grade before deleting it.']);
        }

        $grade->delete();

        return redirect()->route('admin.compensation.grades.index')->with('status', 'Salary grade deleted.');
    }

    /**
     * @return array{companies: Collection<int, Company>, salaryStructures: Collection<int, SalaryStructure>}
     */
    private function formData(): array
    {
        return [
            'companies' => Company::orderBy('name')->get(),
            'salaryStructures' => SalaryStructure::with('company')->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?SalaryGrade $grade = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'salary_structure_id' => ['required', Rule::exists('salary_structures', 'id')->where('company_id', $request->input('company_id'))],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('salary_grades', 'code')->where('company_id', $request->input('company_id'))->ignore($grade?->id),
            ],
            'min_salary' => ['required', 'numeric', 'min:0'],
            'mid_salary' => ['nullable', 'numeric', 'gte:min_salary'],
            'max_salary' => ['required', 'numeric', 'gte:min_salary'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $grade === null);

        return $validated;
    }
}
