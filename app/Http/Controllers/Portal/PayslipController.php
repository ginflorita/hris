<?php

namespace App\Http\Controllers\Portal;

use App\Enums\PayrollPeriodStatus;
use App\Enums\PayslipAccessAction;
use App\Http\Controllers\Controller;
use App\Models\PayrollItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Blueprint §17 "Payslip Security": never assume an authenticated user
 * may access any payslip. Every action here checks
 * payroll_item.employee_id === auth()->user()->employee_id -- there is
 * no permission-based bypass on these routes (unlike the admin payslip
 * download in PayrollItemController, which is a separate, payroll.export
 * -gated route to the same data) -- see CLAUDE.md "Digital Payslip
 * Portal" for why keeping the two paths separate is simpler than
 * replicating blueprint's exact "unless they have a payroll permission"
 * clause here too.
 */
class PayslipController extends Controller
{
    public function index(Request $request): View
    {
        $employeeId = $request->user()->employee_id;

        return view('portal.payslips.index', [
            'linked' => $employeeId !== null,
            'payrollItems' => $employeeId === null
                ? collect()
                : PayrollItem::query()
                    ->where('employee_id', $employeeId)
                    ->whereHas('payrollPeriod', fn ($q) => $q->where('status', PayrollPeriodStatus::Published))
                    ->with('payrollPeriod')
                    ->get()
                    ->sortByDesc(fn (PayrollItem $item) => $item->payrollPeriod->start_date),
        ]);
    }

    public function show(Request $request, PayrollItem $payrollItem): View
    {
        $this->authorizeOwnership($request, $payrollItem);

        $payrollItem->load(['payrollPeriod', 'taxTable', 'lines', 'contributions']);
        $this->logAccess($request, $payrollItem, PayslipAccessAction::Viewed);

        return view('portal.payslips.show', ['payrollItem' => $payrollItem]);
    }

    public function download(Request $request, PayrollItem $payrollItem): Response
    {
        $this->authorizeOwnership($request, $payrollItem);

        $payrollItem->load(['employee', 'company', 'payrollPeriod', 'lines', 'contributions']);
        $this->logAccess($request, $payrollItem, PayslipAccessAction::Downloaded);

        $pdf = Pdf::loadView('payroll.payslip-pdf', ['payrollItem' => $payrollItem]);
        $filename = 'payslip-'.$payrollItem->payrollPeriod->start_date->format('Y-m').'.pdf';

        return $pdf->download($filename);
    }

    private function authorizeOwnership(Request $request, PayrollItem $payrollItem): void
    {
        abort_unless($payrollItem->employee_id === $request->user()->employee_id, 404);
        abort_unless($payrollItem->payrollPeriod->status === PayrollPeriodStatus::Published, 404);
    }

    private function logAccess(Request $request, PayrollItem $payrollItem, PayslipAccessAction $action): void
    {
        $payrollItem->accessLogs()->create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
