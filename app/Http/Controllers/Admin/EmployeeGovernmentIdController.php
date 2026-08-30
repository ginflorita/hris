<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GovernmentIdType;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeGovernmentId;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeGovernmentIdController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('employees.update');

        $employee->governmentIds()->create($this->validated($request));

        return back()->with('status', 'Government ID added.');
    }

    public function update(Request $request, Employee $employee, EmployeeGovernmentId $governmentId): RedirectResponse
    {
        $this->authorize('employees.update');
        abort_unless($governmentId->employee_id === $employee->id, 404);

        $governmentId->update($this->validated($request));

        return back()->with('status', 'Government ID updated.');
    }

    public function destroy(Employee $employee, EmployeeGovernmentId $governmentId): RedirectResponse
    {
        $this->authorize('employees.update');
        abort_unless($governmentId->employee_id === $employee->id, 404);

        $governmentId->delete();

        return back()->with('status', 'Government ID removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'id_type' => ['required', Rule::enum(GovernmentIdType::class)],
            'id_number' => ['required', 'string', 'max:255'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:issued_at'],
        ]);
    }
}
