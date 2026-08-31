<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaxTable;
use App\Models\TaxTableBracket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaxTableBracketController extends Controller
{
    public function store(Request $request, TaxTable $taxTable): RedirectResponse
    {
        $this->authorize('payroll.create');

        $taxTable->brackets()->create($this->validated($request));

        return back()->with('status', 'Bracket added.');
    }

    public function update(Request $request, TaxTable $taxTable, TaxTableBracket $bracket): RedirectResponse
    {
        $this->authorize('payroll.create');
        abort_unless($bracket->tax_table_id === $taxTable->id, 404);

        $bracket->update($this->validated($request));

        return back()->with('status', 'Bracket updated.');
    }

    public function destroy(TaxTable $taxTable, TaxTableBracket $bracket): RedirectResponse
    {
        $this->authorize('payroll.create');
        abort_unless($bracket->tax_table_id === $taxTable->id, 404);

        $bracket->delete();

        return back()->with('status', 'Bracket removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'order' => ['nullable', 'integer', 'min:0'],
            'min_income' => ['required', 'numeric', 'min:0'],
            'max_income' => ['nullable', 'numeric', 'gt:min_income'],
            'base_tax' => ['required', 'numeric', 'min:0'],
            'excess_rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $validated['order'] ??= 0;

        return $validated;
    }
}
