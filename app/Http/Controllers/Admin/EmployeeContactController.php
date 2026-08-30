<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContactType;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeContactController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('employees.update');

        $this->save($employee, $request, null);

        return back()->with('status', 'Contact added.');
    }

    public function update(Request $request, Employee $employee, EmployeeContact $contact): RedirectResponse
    {
        $this->authorize('employees.update');
        abort_unless($contact->employee_id === $employee->id, 404);

        $this->save($employee, $request, $contact);

        return back()->with('status', 'Contact updated.');
    }

    public function destroy(Employee $employee, EmployeeContact $contact): RedirectResponse
    {
        $this->authorize('employees.update');
        abort_unless($contact->employee_id === $employee->id, 404);

        $contact->delete();

        return back()->with('status', 'Contact removed.');
    }

    private function save(Employee $employee, Request $request, ?EmployeeContact $contact): void
    {
        $validated = $request->validate([
            'type' => ['required', Rule::enum(ContactType::class)],
            'value' => ['required', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
        ]);
        $validated['is_primary'] = $request->boolean('is_primary');

        DB::transaction(function () use ($employee, $validated, $contact) {
            if ($validated['is_primary']) {
                $employee->contacts()->where('id', '!=', $contact?->id)->update(['is_primary' => false]);
            }

            if ($contact) {
                $contact->update($validated);
            } else {
                $employee->contacts()->create($validated);
            }
        });
    }
}
