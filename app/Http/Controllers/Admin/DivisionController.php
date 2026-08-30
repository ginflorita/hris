<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Division;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DivisionController extends Controller
{
    public function index(): View
    {
        $this->authorize('organization.view');

        return view('admin.organization.divisions.index', [
            'divisions' => Division::with('company')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('organization.manage');

        return view('admin.organization.divisions.create', ['companies' => $this->companies()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('organization.manage');

        Division::create($this->validated($request));

        return redirect()->route('admin.organization.divisions.index')->with('status', 'Division created.');
    }

    public function edit(Division $division): View
    {
        $this->authorize('organization.manage');

        return view('admin.organization.divisions.edit', ['division' => $division, 'companies' => $this->companies()]);
    }

    public function update(Request $request, Division $division): RedirectResponse
    {
        $this->authorize('organization.manage');

        $division->update($this->validated($request, $division));

        return redirect()->route('admin.organization.divisions.index')->with('status', 'Division updated.');
    }

    public function destroy(Division $division): RedirectResponse
    {
        $this->authorize('organization.manage');

        if ($division->departments()->exists()) {
            return back()->withErrors(['division' => 'Reassign or remove this division\'s departments before deleting it.']);
        }

        $division->delete();

        return redirect()->route('admin.organization.divisions.index')->with('status', 'Division deleted.');
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
    private function validated(Request $request, ?Division $division = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('divisions', 'code')->where('company_id', $request->input('company_id'))->ignore($division?->id),
            ],
        ]);

        // See CompanyController::validated() for why the fallback differs
        // between create (absent -> true) and update (absent -> false).
        $validated['is_active'] = $request->boolean('is_active', $division === null);

        return $validated;
    }
}
