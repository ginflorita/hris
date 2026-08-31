<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Leave\Services\LeaveBalanceService;
use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveTransactionType;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Blueprint §18 "View leave" / "Request leave". Unlike
 * Admin\LeaveRequestController::store() (which lets an HR user pick any
 * employee_id), every action here is hard-scoped to
 * auth()->user()->employee_id -- there is no employee_id input field.
 * approve()/reject() stay admin/manager-only (Admin\
 * LeaveRequestController); this controller only covers what an employee
 * does to their own requests: view, submit, cancel.
 */
class LeaveController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;

        if ($employee) {
            $employee->load(['leaveBalances.leaveType', 'leaveRequests' => fn ($q) => $q->with('leaveType')->orderByDesc('start_date')]);
        }

        return view('portal.leave.index', ['employee' => $employee]);
    }

    public function create(Request $request): View
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        return view('portal.leave.create', [
            'leaveTypes' => LeaveType::where('company_id', $employee->company_id)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        $validated = $request->validate([
            'leave_type_id' => ['required', Rule::exists('leave_types', 'id')->where('company_id', $employee->company_id)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $daysCount = Carbon::parse($validated['start_date'])->diffInDays(Carbon::parse($validated['end_date'])) + 1;

        LeaveRequest::create([
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'leave_type_id' => $validated['leave_type_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'days_count' => $daysCount,
            'reason' => $validated['reason'] ?? null,
            'status' => LeaveRequestStatus::Pending,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('portal.leave.index')->with('status', 'Leave request submitted.');
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest, LeaveBalanceService $service): RedirectResponse
    {
        abort_unless($leaveRequest->employee_id === $request->user()->employee_id, 404);
        abort_if(in_array($leaveRequest->status, [LeaveRequestStatus::Rejected, LeaveRequestStatus::Cancelled], true), 422, 'This request is already closed.');

        if ($leaveRequest->status === LeaveRequestStatus::Approved) {
            $service->applyTransaction(
                employee: $leaveRequest->employee,
                leaveType: $leaveRequest->leaveType,
                type: LeaveTransactionType::Reversal,
                amount: (float) $leaveRequest->days_count,
                date: now()->toDateString(),
                reason: "Leave request #{$leaveRequest->id} cancelled by employee",
                leaveRequest: $leaveRequest,
                createdBy: $request->user()->id,
            );
        }

        $leaveRequest->update(['status' => LeaveRequestStatus::Cancelled, 'cancelled_at' => now()]);

        return back()->with('status', 'Leave request cancelled.');
    }
}
