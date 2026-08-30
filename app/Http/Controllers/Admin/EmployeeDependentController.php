<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeDependent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeDependentController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('employees.update');

        $employee->dependents()->create($this->validated($request));

        return back()->with('status', 'Dependent added.');
    }

    public function update(Request $request, Employee $employee, EmployeeDependent $dependent): RedirectResponse
    {
        $this->authorize('employees.update');
        abort_unless($dependent->employee_id === $employee->id, 404);

        $dependent->update($this->validated($request));

        return back()->with('status', 'Dependent updated.');
    }

    public function destroy(Employee $employee, EmployeeDependent $dependent): RedirectResponse
    {
        $this->authorize('employees.update');
        abort_unless($dependent->employee_id === $employee->id, 404);

        $dependent->delete();

        return back()->with('status', 'Dependent removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'relationship' => ['required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before:today'],
        ]);
        $validated['is_beneficiary'] = $request->boolean('is_beneficiary');

        return $validated;
    }
}
