<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeScheduleController extends Controller
{
    /**
     * Append-only, same pattern as EmploymentController::store() — closes
     * the previously current schedule assignment instead of overwriting it.
     */
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('employees.update');

        $validated = $request->validate([
            'schedule_id' => ['required', Rule::exists('schedules', 'id')->where('company_id', $employee->company_id)],
            'effective_date' => ['required', 'date'],
        ]);

        $validated['created_by'] = $request->user()->id;

        DB::transaction(function () use ($employee, $validated) {
            $current = $employee->employeeSchedules()->whereNull('end_date')->first();

            if ($current) {
                $current->update(['end_date' => Carbon::parse($validated['effective_date'])->subDay()]);
            }

            $employee->employeeSchedules()->create($validated);
        });

        return back()->with('status', 'Schedule assigned.');
    }
}
