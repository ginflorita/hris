<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeEmergencyContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeEmergencyContactController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('employees.update');

        $this->save($employee, $request, null);

        return back()->with('status', 'Emergency contact added.');
    }

    public function update(Request $request, Employee $employee, EmployeeEmergencyContact $emergencyContact): RedirectResponse
    {
        $this->authorize('employees.update');
        abort_unless($emergencyContact->employee_id === $employee->id, 404);

        $this->save($employee, $request, $emergencyContact);

        return back()->with('status', 'Emergency contact updated.');
    }

    public function destroy(Employee $employee, EmployeeEmergencyContact $emergencyContact): RedirectResponse
    {
        $this->authorize('employees.update');
        abort_unless($emergencyContact->employee_id === $employee->id, 404);

        $emergencyContact->delete();

        return back()->with('status', 'Emergency contact removed.');
    }

    private function save(Employee $employee, Request $request, ?EmployeeEmergencyContact $emergencyContact): void
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'relationship' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);
        $validated['is_primary'] = $request->boolean('is_primary');

        DB::transaction(function () use ($employee, $validated, $emergencyContact) {
            if ($validated['is_primary']) {
                $employee->emergencyContacts()->where('id', '!=', $emergencyContact?->id)->update(['is_primary' => false]);
            }

            if ($emergencyContact) {
                $emergencyContact->update($validated);
            } else {
                $employee->emergencyContacts()->create($validated);
            }
        });
    }
}
