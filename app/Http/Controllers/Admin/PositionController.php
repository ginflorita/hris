<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\JobGrade;
use App\Models\JobLevel;
use App\Models\Position;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PositionController extends Controller
{
    public function index(): View
    {
        $this->authorize('organization.view');

        return view('admin.organization.positions.index', [
            'positions' => Position::with(['company', 'department', 'jobLevel', 'jobGrade'])->orderBy('title')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('organization.manage');

        return view('admin.organization.positions.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('organization.manage');

        Position::create($this->validated($request));

        return redirect()->route('admin.organization.positions.index')->with('status', 'Position created.');
    }

    public function edit(Position $position): View
    {
        $this->authorize('organization.manage');

        return view('admin.organization.positions.edit', ['position' => $position, ...$this->formData()]);
    }

    public function update(Request $request, Position $position): RedirectResponse
    {
        $this->authorize('organization.manage');

        $position->update($this->validated($request, $position));

        return redirect()->route('admin.organization.positions.index')->with('status', 'Position updated.');
    }

    public function destroy(Position $position): RedirectResponse
    {
        $this->authorize('organization.manage');

        $position->delete();

        return redirect()->route('admin.organization.positions.index')->with('status', 'Position deleted.');
    }

    /**
     * @return array{companies: Collection<int, Company>, departments: Collection<int, Department>, jobLevels: Collection<int, JobLevel>, jobGrades: Collection<int, JobGrade>}
     */
    private function formData(): array
    {
        return [
            'companies' => Company::orderBy('name')->get(),
            'departments' => Department::with('company')->orderBy('name')->get(),
            'jobLevels' => JobLevel::with('company')->orderBy('rank')->get(),
            'jobGrades' => JobGrade::with('company')->orderBy('rank')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Position $position = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'department_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where('company_id', $request->input('company_id')),
            ],
            'job_level_id' => [
                'nullable',
                Rule::exists('job_levels', 'id')->where('company_id', $request->input('company_id')),
            ],
            'job_grade_id' => [
                'nullable',
                Rule::exists('job_grades', 'id')->where('company_id', $request->input('company_id')),
            ],
            'title' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('positions', 'code')->where('company_id', $request->input('company_id'))->ignore($position?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $position === null);

        return $validated;
    }
}
