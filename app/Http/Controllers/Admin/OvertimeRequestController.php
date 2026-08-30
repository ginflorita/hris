<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OvertimeStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OvertimeRequestController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('attendance.view');

        $query = OvertimeRequest::with('employee')->orderByDesc('date');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return view('admin.attendance.overtime.index', [
            'overtimeRequests' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only('status'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('attendance.manage');

        return view('admin.attendance.overtime.create', ['employees' => Employee::orderBy('last_name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('attendance.manage');

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'date' => ['required', 'date'],
            'requested_hours' => ['required', 'numeric', 'min:0.5', 'max:24'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);

        OvertimeRequest::create([
            ...$validated,
            'company_id' => $employee->company_id,
            'status' => OvertimeStatus::Pending,
            'requested_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.attendance.overtime.index')->with('status', 'Overtime request submitted.');
    }

    public function approve(Request $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        $this->authorize('attendance.manage');

        $overtimeRequest->update([
            'status' => OvertimeStatus::Approved,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return back()->with('status', 'Overtime request approved.');
    }

    public function reject(Request $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        $this->authorize('attendance.manage');

        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:500']]);

        $overtimeRequest->update([
            'status' => OvertimeStatus::Rejected,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return back()->with('status', 'Overtime request rejected.');
    }
}
