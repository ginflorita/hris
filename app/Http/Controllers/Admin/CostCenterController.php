<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Department;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CostCenterController extends Controller
{
    public function index(): View
    {
        $this->authorize('organization.view');

        return view('admin.organization.cost-centers.index', [
            'costCenters' => CostCenter::with(['company', 'department'])->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('organization.manage');

        return view('admin.organization.cost-centers.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('organization.manage');

        CostCenter::create($this->validated($request));

        return redirect()->route('admin.organization.cost-centers.index')->with('status', 'Cost center created.');
    }

    public function edit(CostCenter $costCenter): View
    {
        $this->authorize('organization.manage');

        return view('admin.organization.cost-centers.edit', ['costCenter' => $costCenter, ...$this->formData()]);
    }

    public function update(Request $request, CostCenter $costCenter): RedirectResponse
    {
        $this->authorize('organization.manage');

        $costCenter->update($this->validated($request, $costCenter));

        return redirect()->route('admin.organization.cost-centers.index')->with('status', 'Cost center updated.');
    }

    public function destroy(CostCenter $costCenter): RedirectResponse
    {
        $this->authorize('organization.manage');

        $costCenter->delete();

        return redirect()->route('admin.organization.cost-centers.index')->with('status', 'Cost center deleted.');
    }

    /**
     * @return array{companies: Collection<int, Company>, departments: Collection<int, Department>}
     */
    private function formData(): array
    {
        return [
            'companies' => Company::orderBy('name')->get(),
            'departments' => Department::with('company')->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?CostCenter $costCenter = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'department_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where('company_id', $request->input('company_id')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('cost_centers', 'code')->where('company_id', $request->input('company_id'))->ignore($costCenter?->id),
            ],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $costCenter === null);

        return $validated;
    }
}
