<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EmploymentChangeType;
use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Enums\WorkArrangement;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmploymentController extends Controller
{
    /**
     * Employment rows are append-only: this always inserts a new row and,
     * if one exists, closes the previously current row by setting its
     * end_date to the day before the new row's effective_date. There is
     * no update()/destroy() — see CLAUDE.md "Employment" for why.
     */
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('employees.update');

        $validated = $request->validate([
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('company_id', $employee->company_id)],
            'position_id' => ['nullable', Rule::exists('positions', 'id')->where('company_id', $employee->company_id)],
            'salary_grade_id' => ['nullable', Rule::exists('salary_grades', 'id')->where('company_id', $employee->company_id)],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('company_id', $employee->company_id)],
            'location_id' => ['nullable', Rule::exists('locations', 'id')->where('company_id', $employee->company_id)],
            'manager_id' => ['nullable', Rule::exists('employees', 'id')->where('company_id', $employee->company_id)],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'work_arrangement' => ['nullable', Rule::enum(WorkArrangement::class)],
            'status' => ['required', Rule::enum(EmploymentStatus::class)],
            'change_type' => ['required', Rule::enum(EmploymentChangeType::class)],
            'effective_date' => ['required', 'date'],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'probation_ends_at' => ['nullable', 'date'],
            'regularized_at' => ['nullable', 'date'],
            'contract_start_date' => ['nullable', 'date'],
            'contract_end_date' => ['nullable', 'date', 'after_or_equal:contract_start_date'],
            'separation_reason' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['manager_id'] = $request->filled('manager_id') ? (int) $request->input('manager_id') : null;

        if ($validated['manager_id'] === $employee->id) {
            return back()->withErrors(['manager_id' => 'An employee cannot be their own manager.'])->withInput();
        }

        $validated['company_id'] = $employee->company_id;
        $validated['created_by'] = $request->user()->id;

        DB::transaction(function () use ($employee, $validated) {
            $current = $employee->employments()->whereNull('end_date')->first();

            if ($current) {
                $current->update(['end_date' => Carbon::parse($validated['effective_date'])->subDay()]);
            }

            $employee->employments()->create($validated);
        });

        return back()->with('status', 'Employment record added.');
    }
}
