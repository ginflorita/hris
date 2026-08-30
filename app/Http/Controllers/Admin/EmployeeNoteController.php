<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeNoteController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('employees.update');

        $employee->notes()->create($this->validated($request) + ['created_by' => $request->user()->id]);

        return back()->with('status', 'Note added.');
    }

    public function update(Request $request, Employee $employee, EmployeeNote $note): RedirectResponse
    {
        $this->authorize('employees.update');
        abort_unless($note->employee_id === $employee->id, 404);

        $note->update($this->validated($request));

        return back()->with('status', 'Note updated.');
    }

    public function destroy(Employee $employee, EmployeeNote $note): RedirectResponse
    {
        $this->authorize('employees.update');
        abort_unless($note->employee_id === $employee->id, 404);

        $note->delete();

        return back()->with('status', 'Note removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);
        $validated['is_confidential'] = $request->boolean('is_confidential', true);

        return $validated;
    }
}
