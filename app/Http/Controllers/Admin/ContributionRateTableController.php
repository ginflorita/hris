<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GovernmentAgency;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\ContributionRateTable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContributionRateTableController extends Controller
{
    public function index(): View
    {
        $this->authorize('payroll.view');

        return view('admin.payroll.contribution-rate-tables.index', [
            'contributionRateTables' => ContributionRateTable::with('company')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('payroll.create');

        return view('admin.payroll.contribution-rate-tables.create', ['companies' => $this->companies()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('payroll.create');

        $table = ContributionRateTable::create($this->validated($request));

        return redirect()->route('admin.payroll.contribution-rate-tables.show', $table)
            ->with('status', 'Contribution rate table created. Add its brackets below.');
    }

    public function show(ContributionRateTable $contributionRateTable): View
    {
        $this->authorize('payroll.view');

        return view('admin.payroll.contribution-rate-tables.show', [
            'contributionRateTable' => $contributionRateTable->load('brackets'),
        ]);
    }

    public function edit(ContributionRateTable $contributionRateTable): View
    {
        $this->authorize('payroll.create');

        return view('admin.payroll.contribution-rate-tables.edit', [
            'contributionRateTable' => $contributionRateTable,
            'companies' => $this->companies(),
        ]);
    }

    public function update(Request $request, ContributionRateTable $contributionRateTable): RedirectResponse
    {
        $this->authorize('payroll.create');

        $contributionRateTable->update($this->validated($request, $contributionRateTable));

        return redirect()->route('admin.payroll.contribution-rate-tables.index')->with('status', 'Contribution rate table updated.');
    }

    public function destroy(ContributionRateTable $contributionRateTable): RedirectResponse
    {
        $this->authorize('payroll.create');

        $contributionRateTable->brackets()->delete();
        $contributionRateTable->delete();

        return redirect()->route('admin.payroll.contribution-rate-tables.index')->with('status', 'Contribution rate table deleted.');
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
    private function validated(Request $request, ?ContributionRateTable $table = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'agency' => ['required', Rule::enum(GovernmentAgency::class)],
            'name' => ['required', 'string', 'max:255'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $table === null);

        return $validated;
    }
}
