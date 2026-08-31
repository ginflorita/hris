<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\TaxTable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxTableController extends Controller
{
    public function index(): View
    {
        $this->authorize('payroll.view');

        return view('admin.payroll.tax-tables.index', [
            'taxTables' => TaxTable::with('company')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('payroll.create');

        return view('admin.payroll.tax-tables.create', ['companies' => $this->companies()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('payroll.create');

        $table = TaxTable::create($this->validated($request));

        return redirect()->route('admin.payroll.tax-tables.show', $table)
            ->with('status', 'Tax table created. Add its brackets below.');
    }

    public function show(TaxTable $taxTable): View
    {
        $this->authorize('payroll.view');

        return view('admin.payroll.tax-tables.show', [
            'taxTable' => $taxTable->load('brackets'),
        ]);
    }

    public function edit(TaxTable $taxTable): View
    {
        $this->authorize('payroll.create');

        return view('admin.payroll.tax-tables.edit', [
            'taxTable' => $taxTable,
            'companies' => $this->companies(),
        ]);
    }

    public function update(Request $request, TaxTable $taxTable): RedirectResponse
    {
        $this->authorize('payroll.create');

        $taxTable->update($this->validated($request, $taxTable));

        return redirect()->route('admin.payroll.tax-tables.index')->with('status', 'Tax table updated.');
    }

    public function destroy(TaxTable $taxTable): RedirectResponse
    {
        $this->authorize('payroll.create');

        $taxTable->brackets()->delete();
        $taxTable->delete();

        return redirect()->route('admin.payroll.tax-tables.index')->with('status', 'Tax table deleted.');
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
    private function validated(Request $request, ?TaxTable $table = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $table === null);

        return $validated;
    }
}
