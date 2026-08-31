<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PayrollPeriodStatus;
use App\Http\Controllers\Controller;
use App\Models\PayrollItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PayrollItemController extends Controller
{
    /**
     * Periods eligible for a payslip PDF -- generated once the numbers
     * are official (Finalized) through Published; blueprint §14's
     * lifecycle diagram places "Generate Payslips" between Lock and
     * Publish, so Finalized/Locked give an admin a preview window before
     * the employee-facing Publish step. See Payroll Approval in
     * CLAUDE.md.
     */
    private const PAYSLIP_ELIGIBLE_STATUSES = [
        PayrollPeriodStatus::Finalized,
        PayrollPeriodStatus::Locked,
        PayrollPeriodStatus::Published,
    ];

    public function show(PayrollItem $payrollItem): View
    {
        $this->authorize('payroll.view');

        return view('admin.payroll.payroll-items.show', [
            'payrollItem' => $payrollItem->load(['employee', 'payrollPeriod', 'taxTable', 'lines', 'contributions']),
        ]);
    }

    public function downloadPayslip(PayrollItem $payrollItem): Response|RedirectResponse
    {
        $this->authorize('payroll.export');

        $payrollItem->load(['employee', 'company', 'payrollPeriod', 'lines', 'contributions']);

        if (! in_array($payrollItem->payrollPeriod->status, self::PAYSLIP_ELIGIBLE_STATUSES, true)) {
            return back()->withErrors(['payrollItem' => 'This period must be finalized before its payslips can be downloaded.']);
        }

        $pdf = Pdf::loadView('payroll.payslip-pdf', ['payrollItem' => $payrollItem]);

        $filename = 'payslip-'.$payrollItem->employee->employee_number.'-'.$payrollItem->payrollPeriod->start_date->format('Y-m').'.pdf';

        return $pdf->download($filename);
    }
}
