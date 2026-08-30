<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SalaryStructure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SalaryStructureController extends Controller
{
    public function index(): View
    {
        $this->authorize('organization.view');

        return view('admin.compensation.structures.index', [
            'salaryStructures' => SalaryStructure::with('company')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('organization.manage');

        return view('admin.compensation.structures.create', ['companies' => $this->companies()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('organization.manage');

        SalaryStructure::create($this->validated($request));

        return redirect()->route('admin.compensation.structures.index')->with('status', 'Salary structure created.');
    }

    public function edit(SalaryStructure $structure): View
    {
        $this->authorize('organization.manage');

        return view('admin.compensation.structures.edit', ['salaryStructure' => $structure, 'companies' => $this->companies()]);
    }

    public function update(Request $request, SalaryStructure $structure): RedirectResponse
    {
        $this->authorize('organization.manage');

        $structure->update($this->validated($request, $structure));

        return redirect()->route('admin.compensation.structures.index')->with('status', 'Salary structure updated.');
    }

    public function destroy(SalaryStructure $structure): RedirectResponse
    {
        $this->authorize('organization.manage');

        if ($structure->salaryGrades()->exists()) {
            return back()->withErrors(['salaryStructure' => 'Remove the salary grades under this structure before deleting it.']);
        }

        $structure->delete();

        return redirect()->route('admin.compensation.structures.index')->with('status', 'Salary structure deleted.');
    }

    /**
     * @return Collection<int, Company>
     */
    private function companies(): Collection
    {
        return Company::orderBy('name')->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?SalaryStructure $structure = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('salary_structures', 'code')->where('company_id', $request->input('company_id'))->ignore($structure?->id),
            ],
            'effective_date' => ['required', 'date'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $structure === null);

        return $validated;
    }
}
