<?php

namespace App\Http\Controllers\Portal;

use App\Enums\OvertimeStatus;
use App\Http\Controllers\Controller;
use App\Models\OvertimeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Blueprint §18 "View overtime" / "Request overtime" -- same
 * self-scoped shape as Portal\LeaveController. Approve/reject stay
 * admin/manager-only (Admin\OvertimeRequestController).
 */
class OvertimeController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;

        if ($employee) {
            $employee->load(['overtimeRequests' => fn ($q) => $q->orderByDesc('date')]);
        }

        return view('portal.overtime.index', ['employee' => $employee]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'requested_hours' => ['required', 'numeric', 'min:0.5', 'max:24'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        OvertimeRequest::create([
            ...$validated,
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'status' => OvertimeStatus::Pending,
            'requested_by' => $request->user()->id,
        ]);

        return redirect()->route('portal.overtime.index')->with('status', 'Overtime request submitted.');
    }
}
