<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContributionRateBracket;
use App\Models\ContributionRateTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContributionRateBracketController extends Controller
{
    public function store(Request $request, ContributionRateTable $contributionRateTable): RedirectResponse
    {
        $this->authorize('payroll.create');

        $contributionRateTable->brackets()->create($this->validated($request));

        return back()->with('status', 'Bracket added.');
    }

    public function update(Request $request, ContributionRateTable $contributionRateTable, ContributionRateBracket $bracket): RedirectResponse
    {
        $this->authorize('payroll.create');
        abort_unless($bracket->contribution_rate_table_id === $contributionRateTable->id, 404);

        $bracket->update($this->validated($request));

        return back()->with('status', 'Bracket updated.');
    }

    public function destroy(ContributionRateTable $contributionRateTable, ContributionRateBracket $bracket): RedirectResponse
    {
        $this->authorize('payroll.create');
        abort_unless($bracket->contribution_rate_table_id === $contributionRateTable->id, 404);

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
            'min_salary' => ['required', 'numeric', 'min:0'],
            'max_salary' => ['nullable', 'numeric', 'gt:min_salary'],
            'employee_amount' => ['required', 'numeric', 'min:0'],
            'employer_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $validated['order'] ??= 0;

        return $validated;
    }
}
