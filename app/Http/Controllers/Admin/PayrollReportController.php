<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PayrollItemLineType;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Blueprint §3 item 54, "Payroll Reports". Gated by `payroll.view`, not
 * the `reports.view` this module's other new report (HR, 19a) uses --
 * payroll cost/deduction/contribution/tax figures are exactly the data
 * CLAUDE.md's payroll rules already protect tightly (no seeded role
 * except Payroll Administrator holds any `payroll.*` permission), and
 * `reports.view` alone is also held by HR Administrator, who has no
 * business seeing aggregate payroll cost just by virtue of running HR
 * headcount reports. Matches the same "reuse the module's own
 * permission" choice `AttendanceReportController`/`LeaveReportController`
 * (Phases 8/9) already made for their own reports.
 */
class PayrollReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('payroll.view');

        $companyId = $request->integer('company_id');

        $periods = PayrollPeriod::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        $selectedPeriodId = $request->integer('payroll_period_id');
        $selectedPeriod = $periods->firstWhere('id', $selectedPeriodId) ?? $periods->first();

        $items = $selectedPeriod
            ? PayrollItem::where('payroll_period_id', $selectedPeriod->id)->with(['lines', 'contributions'])->get()
            : collect();

        $lines = $items->flatMap->lines;
        $contributions = $items->flatMap->contributions;

        $byDeductionCategory = $lines
            ->where('type', PayrollItemLineType::Deduction)
            ->groupBy('category')
            ->map(fn ($rows) => $rows->sum('amount'))
            ->sortDesc();

        $byAgency = $contributions
            ->groupBy(fn ($contribution) => $contribution->agency->value)
            ->map(fn ($rows) => [
                'employee' => $rows->sum('employee_amount'),
                'employer' => $rows->sum('employer_amount'),
            ]);

        $recentPeriods = $periods->take(6)->map(fn (PayrollPeriod $period) => [
            'period' => $period,
            'netPay' => PayrollItem::where('payroll_period_id', $period->id)->sum('net_pay'),
        ]);

        return view('admin.reports.payroll.index', [
            'companies' => Company::orderBy('name')->get(),
            'companyId' => $companyId,
            'periods' => $periods,
            'selectedPeriod' => $selectedPeriod,
            'totals' => [
                'employeeCount' => $items->count(),
                'grossEarnings' => $items->sum('gross_earnings'),
                'totalDeductions' => $items->sum('total_deductions'),
                'employeeContributions' => $items->sum('total_employee_contributions'),
                'employerContributions' => $items->sum('total_employer_contributions'),
                'tax' => $items->sum('tax_amount'),
                'netPay' => $items->sum('net_pay'),
            ],
            'byDeductionCategory' => $byDeductionCategory,
            'byAgency' => $byAgency,
            'recentPeriods' => $recentPeriods,
        ]);
    }
}
