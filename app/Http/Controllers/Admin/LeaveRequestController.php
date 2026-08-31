<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Leave\Services\LeaveBalanceService;
use App\Domain\Security\Services\DataScopeResolver;
use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveTransactionType;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function index(Request $request, DataScopeResolver $scopeResolver): View
    {
        $this->authorize('leave.view');

        $query = LeaveRequest::with(['employee', 'leaveType'])->orderByDesc('start_date');

        $employeeIds = $scopeResolver->employeeIdsFor($request->user(), 'leave.view');
        if ($employeeIds !== null) {
            $query->whereIn('employee_id', $employeeIds);
        }

        if ($employeeId = $request->integer('employee_id')) {
            $query->where('employee_id', $employeeId);
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return view('admin.leave.requests.index', [
            'leaveRequests' => $query->paginate(20)->withQueryString(),
            'employees' => Employee::orderBy('last_name')->get(),
            'filters' => $request->only(['employee_id', 'status']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('leave.create');

        return view('admin.leave.requests.create', [
            'employees' => Employee::orderBy('last_name')->get(),
            'leaveTypes' => LeaveType::with('company')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('leave.create');

        $request->validate(['employee_id' => ['required', 'exists:employees,id']]);
        $employee = Employee::findOrFail($request->input('employee_id'));

        $validated = $request->validate([
            'leave_type_id' => ['required', Rule::exists('leave_types', 'id')->where('company_id', $employee->company_id)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $leaveType = LeaveType::findOrFail($validated['leave_type_id']);

        $daysCount = Carbon::parse($validated['start_date'])->diffInDays(Carbon::parse($validated['end_date'])) + 1;

        LeaveRequest::create([
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'days_count' => $daysCount,
            'reason' => $validated['reason'] ?? null,
            'status' => LeaveRequestStatus::Pending,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.leave.requests.index')->with('status', 'Leave request submitted.');
    }

    /**
     * Balance is deducted here, on approval — not at submission time —
     * per the blueprint's own workflow (§12: Request -> Manager -> HR ->
     * Approved -> Balance Updated).
     */
    public function approve(Request $request, LeaveRequest $leaveRequest, LeaveBalanceService $service, DataScopeResolver $scopeResolver): RedirectResponse
    {
        $this->authorize('leave.approve');
        $this->authorizeScope($request, $leaveRequest, 'leave.approve', $scopeResolver);

        abort_unless($leaveRequest->status === LeaveRequestStatus::Pending, 422, 'Only pending requests can be approved.');

        $service->applyTransaction(
            employee: $leaveRequest->employee,
            leaveType: $leaveRequest->leaveType,
            type: LeaveTransactionType::Usage,
            amount: -1 * (float) $leaveRequest->days_count,
            date: $leaveRequest->start_date->format('Y-m-d'),
            reason: "Leave request #{$leaveRequest->id} approved",
            leaveRequest: $leaveRequest,
            createdBy: $request->user()->id,
        );

        $leaveRequest->update([
            'status' => LeaveRequestStatus::Approved,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('status', 'Leave request approved.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest, DataScopeResolver $scopeResolver): RedirectResponse
    {
        $this->authorize('leave.reject');
        $this->authorizeScope($request, $leaveRequest, 'leave.reject', $scopeResolver);

        abort_unless($leaveRequest->status === LeaveRequestStatus::Pending, 422, 'Only pending requests can be rejected.');

        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:500']]);

        $leaveRequest->update([
            'status' => LeaveRequestStatus::Rejected,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return back()->with('status', 'Leave request rejected.');
    }

    /**
     * Cancelling an already-approved request reverses the balance
     * deduction via a new ledger entry — the approval's transaction row
     * is never edited or deleted, only offset.
     */
    public function cancel(Request $request, LeaveRequest $leaveRequest, LeaveBalanceService $service): RedirectResponse
    {
        $this->authorize('leave.create');

        abort_if(in_array($leaveRequest->status, [LeaveRequestStatus::Rejected, LeaveRequestStatus::Cancelled], true), 422, 'This request is already closed.');

        if ($leaveRequest->status === LeaveRequestStatus::Approved) {
            $service->applyTransaction(
                employee: $leaveRequest->employee,
                leaveType: $leaveRequest->leaveType,
                type: LeaveTransactionType::Reversal,
                amount: (float) $leaveRequest->days_count,
                date: now()->toDateString(),
                reason: "Leave request #{$leaveRequest->id} cancelled",
                leaveRequest: $leaveRequest,
                createdBy: $request->user()->id,
            );
        }

        $leaveRequest->update([
            'status' => LeaveRequestStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        return back()->with('status', 'Leave request cancelled.');
    }

    /**
     * A Team-scoped holder of $permission (Manager role) may only act on
     * requests from their own direct reports -- see
     * App\Domain\Security\Services\DataScopeResolver. Company-scoped
     * holders (HR Administrator) are unaffected: employeeIdsFor()
     * returns null for them, same access as before this existed.
     */
    private function authorizeScope(Request $request, LeaveRequest $leaveRequest, string $permission, DataScopeResolver $scopeResolver): void
    {
        $employeeIds = $scopeResolver->employeeIdsFor($request->user(), $permission);
        abort_if($employeeIds !== null && ! in_array($leaveRequest->employee_id, $employeeIds, true), 403);
    }
}
