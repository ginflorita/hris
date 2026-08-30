<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Leave\Services\LeaveBalanceService;
use App\Enums\LeaveTransactionType;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveBalanceController extends Controller
{
    public function adjust(Request $request, Employee $employee, LeaveBalanceService $service): RedirectResponse
    {
        $this->authorize('leave.create');

        $validated = $request->validate([
            'leave_type_id' => ['required', Rule::exists('leave_types', 'id')->where('company_id', $employee->company_id)],
            'amount' => ['required', 'numeric', 'min:-365', 'max:365', 'not_in:0'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $leaveType = LeaveType::findOrFail($validated['leave_type_id']);

        $service->applyTransaction(
            employee: $employee,
            leaveType: $leaveType,
            type: LeaveTransactionType::Adjustment,
            amount: (float) $validated['amount'],
            date: now()->toDateString(),
            reason: $validated['reason'],
            createdBy: $request->user()->id,
        );

        return back()->with('status', 'Leave balance adjusted.');
    }
}
