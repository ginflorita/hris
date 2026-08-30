<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(): View
    {
        $this->authorize('organization.view');

        return view('admin.organization.branches.index', [
            'branches' => Branch::with('company')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('organization.manage');

        return view('admin.organization.branches.create', ['companies' => $this->companies()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('organization.manage');

        Branch::create($this->validated($request));

        return redirect()->route('admin.organization.branches.index')->with('status', 'Branch created.');
    }

    public function edit(Branch $branch): View
    {
        $this->authorize('organization.manage');

        return view('admin.organization.branches.edit', ['branch' => $branch, 'companies' => $this->companies()]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $this->authorize('organization.manage');

        $branch->update($this->validated($request, $branch));

        return redirect()->route('admin.organization.branches.index')->with('status', 'Branch updated.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->authorize('organization.manage');

        $branch->delete();

        return redirect()->route('admin.organization.branches.index')->with('status', 'Branch deleted.');
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
    private function validated(Request $request, ?Branch $branch = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('branches', 'code')->where('company_id', $request->input('company_id'))->ignore($branch?->id),
            ],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        // See CompanyController::validated() for why the fallback differs
        // between create (absent -> true) and update (absent -> false).
        $validated['is_active'] = $request->boolean('is_active', $branch === null);

        return $validated;
    }
}
