<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        $this->authorize('organization.view');

        return view('admin.organization.companies.index', [
            'companies' => Company::orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('organization.manage');

        return view('admin.organization.companies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('organization.manage');

        Company::create($this->validated($request));

        return redirect()->route('admin.organization.companies.index')->with('status', 'Company created.');
    }

    public function edit(Company $company): View
    {
        $this->authorize('organization.manage');

        return view('admin.organization.companies.edit', ['company' => $company]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('organization.manage');

        $company->update($this->validated($request, $company));

        return redirect()->route('admin.organization.companies.index')->with('status', 'Company updated.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->authorize('organization.manage');

        $hasChildren = $company->branches()->exists()
            || $company->divisions()->exists()
            || $company->departments()->exists()
            || $company->sections()->exists()
            || $company->teams()->exists()
            || $company->positions()->exists()
            || $company->jobLevels()->exists()
            || $company->jobGrades()->exists()
            || $company->costCenters()->exists();

        if ($hasChildren) {
            return back()->withErrors(['company' => 'Remove this company\'s branches and organization units before deleting it.']);
        }

        $company->delete();

        return redirect()->route('admin.organization.companies.index')->with('status', 'Company deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Company $company = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', Rule::unique('companies', 'code')->ignore($company?->id)],
            'registration_no' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        // An unchecked checkbox submits no field at all, so 'sometimes'
        // would leave is_active untouched on update instead of turning it
        // off — read it explicitly rather than through validation. The
        // fallback when absent differs by direction: on create there's no
        // prior state to preserve, so absent means "use the sensible
        // default" (true); on update the form always renders the
        // checkbox pre-filled with the current value, so absent can only
        // mean the user unchecked it (false).
        $validated['is_active'] = $request->boolean('is_active', $company === null);

        return $validated;
    }
}
