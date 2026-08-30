<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        $this->authorize('organization.view');

        return view('admin.organization.departments.index', [
            'departments' => Department::with(['company', 'division'])->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('organization.manage');

        return view('admin.organization.departments.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('organization.manage');

        Department::create($this->validated($request));

        return redirect()->route('admin.organization.departments.index')->with('status', 'Department created.');
    }

    public function edit(Department $department): View
    {
        $this->authorize('organization.manage');

        return view('admin.organization.departments.edit', ['department' => $department, ...$this->formData()]);
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $this->authorize('organization.manage');

        $department->update($this->validated($request, $department));

        return redirect()->route('admin.organization.departments.index')->with('status', 'Department updated.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('organization.manage');

        $hasChildren = $department->sections()->exists()
            || $department->teams()->exists()
            || $department->positions()->exists()
            || $department->costCenters()->exists();

        if ($hasChildren) {
            return back()->withErrors(['department' => 'Reassign or remove what belongs to this department before deleting it.']);
        }

        $department->delete();

        return redirect()->route('admin.organization.departments.index')->with('status', 'Department deleted.');
    }

    /**
     * @return array{companies: Collection<int, Company>, divisions: Collection<int, Division>}
     */
    private function formData(): array
    {
        return [
            'companies' => Company::orderBy('name')->get(),
            'divisions' => Division::with('company')->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Department $department = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'division_id' => [
                'nullable',
                Rule::exists('divisions', 'id')->where('company_id', $request->input('company_id')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('departments', 'code')->where('company_id', $request->input('company_id'))->ignore($department?->id),
            ],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $department === null);

        return $validated;
    }
}
