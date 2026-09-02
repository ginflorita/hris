<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Append-only, the same shape as EmploymentController -- there is no
 * update()/destroy(), only store(). If a current enrollment exists for
 * the same plan (end_date IS NULL), it's closed first (end_date = new
 * row's effective_date minus one day) in the same transaction, so a
 * contribution change becomes a new row rather than overwriting history.
 */
class EmployeeBenefitEnrollmentController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('benefits.manage');

        $validated = $request->validate([
            'benefit_plan_id' => ['required', Rule::exists('benefit_plans', 'id')->where('company_id', $employee->company_id)],
            'employee_contribution' => ['nullable', 'numeric', 'min:0'],
            'employer_contribution' => ['nullable', 'numeric', 'min:0'],
            'effective_date' => ['required', 'date'],
            'covered_dependent_ids' => ['nullable', 'array'],
            'covered_dependent_ids.*' => [Rule::exists('employee_dependents', 'id')->where('employee_id', $employee->id)],
        ]);

        DB::transaction(function () use ($employee, $request, $validated) {
            $current = $employee->benefitEnrollments()
                ->where('benefit_plan_id', $validated['benefit_plan_id'])
                ->whereNull('end_date')
                ->first();

            if ($current) {
                $current->update(['end_date' => Carbon::parse($validated['effective_date'])->subDay()]);
            }

            $enrollment = $employee->benefitEnrollments()->create([
                'benefit_plan_id' => $validated['benefit_plan_id'],
                'employee_contribution' => $validated['employee_contribution'] ?? null,
                'employer_contribution' => $validated['employer_contribution'] ?? null,
                'effective_date' => $validated['effective_date'],
                'created_by' => $request->user()->id,
            ]);

            $enrollment->coveredDependents()->sync($validated['covered_dependent_ids'] ?? []);
        });

        return back()->with('status', 'Benefit enrollment added.');
    }
}
