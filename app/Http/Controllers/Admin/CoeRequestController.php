<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CoeRequestStatus;
use App\Enums\CoeRequestType;
use App\Http\Controllers\Controller;
use App\Models\CoeRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Blueprint §25: Request COE -> HR Approval -> Generate PDF -> Available
 * in Portal. "Generate PDF" isn't a separate step here -- approve()
 * freezes a snapshot of the employee's current position/department/
 * status/hire-date/salary onto the request row itself, and the PDF is
 * rendered from that frozen snapshot on every download (portal or
 * admin), the same "render on demand from already-immutable data" shape
 * Portal\PayslipController::download() uses for payslips. No separate
 * coe.* permission group -- approving a COE is a per-employee-record
 * action, the same shape Compensation reused employees.view/
 * employees.update for, so this does too.
 */
class CoeRequestController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('employees.view');

        $query = CoeRequest::with(['employee', 'company'])->orderByDesc('created_at');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return view('admin.coe-requests.index', [
            'coeRequests' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only('status'),
        ]);
    }

    /**
     * A WithCompensation request additionally requires
     * employees.salary.view -- blueprint §19's "don't automatically give
     * [access to] salary" rule applied to the one place in this module
     * where compensation could leak onto a document. Standard/
     * WithoutCompensation/EmploymentVerification never touch salary, so
     * employees.update alone is enough for those.
     */
    public function approve(Request $request, CoeRequest $coeRequest): RedirectResponse
    {
        $this->authorize('employees.update');
        abort_unless($coeRequest->status === CoeRequestStatus::Pending, 422, 'Only a pending request can be approved.');

        if ($coeRequest->type === CoeRequestType::WithCompensation) {
            $this->authorize('employees.salary.view');
        }

        $employee = $coeRequest->employee;
        $currentEmployment = $employee->currentEmployment;
        $firstEmployment = $employee->employments()->oldest('effective_date')->first();

        $coeRequest->update([
            'status' => CoeRequestStatus::Approved,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'snapshot_position' => $currentEmployment?->position?->title,
            'snapshot_department' => $currentEmployment?->department?->name,
            'snapshot_employment_status' => $currentEmployment?->status?->value,
            'snapshot_date_hired' => $firstEmployment?->effective_date,
            'snapshot_monthly_salary' => $coeRequest->type === CoeRequestType::WithCompensation
                ? $currentEmployment?->basic_salary
                : null,
        ]);

        return back()->with('status', 'COE request approved. The certificate is ready for download.');
    }

    public function reject(Request $request, CoeRequest $coeRequest): RedirectResponse
    {
        $this->authorize('employees.update');
        abort_unless($coeRequest->status === CoeRequestStatus::Pending, 422, 'Only a pending request can be rejected.');

        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:500']]);

        $coeRequest->update([
            'status' => CoeRequestStatus::Rejected,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return back()->with('status', 'COE request rejected.');
    }

    public function download(CoeRequest $coeRequest): Response
    {
        $this->authorize('employees.view');

        if ($coeRequest->type === CoeRequestType::WithCompensation) {
            $this->authorize('employees.salary.view');
        }

        abort_unless($coeRequest->status === CoeRequestStatus::Approved, 404);

        $coeRequest->load(['employee', 'company']);
        $pdf = Pdf::loadView('documents.coe-pdf', ['coeRequest' => $coeRequest]);

        return $pdf->download('coe-'.$coeRequest->employee->employee_number.'.pdf');
    }
}
