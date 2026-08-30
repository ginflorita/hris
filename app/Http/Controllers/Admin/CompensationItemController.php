<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompensationFrequency;
use App\Enums\CompensationItemType;
use App\Http\Controllers\Controller;
use App\Models\CompensationItem;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompensationItemController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('employees.update');

        $employee->compensationItems()->create([
            ...$this->validated($request, null),
            'company_id' => $employee->company_id,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Compensation item added.');
    }

    public function update(Request $request, Employee $employee, CompensationItem $item): RedirectResponse
    {
        $this->authorize('employees.update');
        abort_unless($item->employee_id === $employee->id, 404);

        $item->update($this->validated($request, $item));

        return back()->with('status', 'Compensation item updated.');
    }

    public function destroy(Employee $employee, CompensationItem $item): RedirectResponse
    {
        $this->authorize('employees.update');
        abort_unless($item->employee_id === $employee->id, 404);

        $item->delete();

        return back()->with('status', 'Compensation item removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?CompensationItem $item): array
    {
        $validated = $request->validate([
            'type' => ['required', Rule::enum(CompensationItemType::class)],
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'frequency' => ['required', Rule::enum(CompensationFrequency::class)],
            'effective_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:effective_date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', $item === null);

        return $validated;
    }
}
