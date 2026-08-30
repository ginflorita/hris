<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AddressType;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeAddressController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('employees.update');

        $this->save($employee, $request, null);

        return back()->with('status', 'Address added.');
    }

    public function update(Request $request, Employee $employee, EmployeeAddress $address): RedirectResponse
    {
        $this->authorize('employees.update');
        abort_unless($address->employee_id === $employee->id, 404);

        $this->save($employee, $request, $address);

        return back()->with('status', 'Address updated.');
    }

    public function destroy(Employee $employee, EmployeeAddress $address): RedirectResponse
    {
        $this->authorize('employees.update');
        abort_unless($address->employee_id === $employee->id, 404);

        $address->delete();

        return back()->with('status', 'Address removed.');
    }

    private function save(Employee $employee, Request $request, ?EmployeeAddress $address): void
    {
        $validated = $request->validate([
            'type' => ['required', Rule::enum(AddressType::class)],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'province_state' => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:255'],
        ]);
        $validated['is_primary'] = $request->boolean('is_primary');

        DB::transaction(function () use ($employee, $validated, $address) {
            if ($validated['is_primary']) {
                $employee->addresses()->where('id', '!=', $address?->id)->update(['is_primary' => false]);
            }

            if ($address) {
                $address->update($validated);
            } else {
                $employee->addresses()->create($validated);
            }
        });
    }
}
