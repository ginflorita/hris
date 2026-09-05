<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Blueprint §3 item 53, "HR Reports" -- headcount and workforce
 * composition. Reads through Employee/Employment/Department/Position,
 * the same "query existing data, no new tables" pattern every report
 * in this module follows (Attendance's and Leave's own reports already
 * set this precedent in Phases 8/9).
 */
class HrReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('reports.view');

        $companyId = $request->integer('company_id');

        $employees = Employee::query()
            ->whereNull('archived_at')
            ->with('currentEmployment.department', 'currentEmployment.position')
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->get();

        $byDepartment = $employees
            ->groupBy(fn (Employee $employee) => $employee->currentEmployment?->department?->name ?? 'Unassigned')
            ->map->count()
            ->sortDesc();

        $byEmploymentType = $employees
            ->groupBy(fn (Employee $employee) => $employee->currentEmployment?->employment_type?->value ?? 'unassigned')
            ->map->count()
            ->sortDesc();

        $byStatus = $employees
            ->groupBy(fn (Employee $employee) => $employee->currentEmployment?->status?->value ?? 'no_current_employment')
            ->map->count()
            ->sortDesc();

        return view('admin.reports.hr.index', [
            'totalActive' => $employees->count(),
            'totalArchived' => Employee::query()
                ->whereNotNull('archived_at')
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->count(),
            'byDepartment' => $byDepartment,
            'byEmploymentType' => $byEmploymentType,
            'byStatus' => $byStatus,
            'companies' => Company::orderBy('name')->get(),
            'companyId' => $companyId,
        ]);
    }
}
