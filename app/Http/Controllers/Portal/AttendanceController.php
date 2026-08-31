<?php

namespace App\Http\Controllers\Portal;

use App\Enums\AttendanceCorrectionRequestStatus;
use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Blueprint §18 "View attendance" / "Request attendance correction".
 * store() only ever targets an Attendance row the caller owns; approving
 * or rejecting the resulting request stays admin/manager-only
 * (Admin\AttendanceCorrectionRequestController).
 */
class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;

        if ($employee) {
            $employee->load([
                'attendances' => fn ($q) => $q->orderByDesc('date')->limit(31),
                'attendanceCorrectionRequests' => fn ($q) => $q->orderByDesc('created_at'),
            ]);
        }

        return view('portal.attendance.index', ['employee' => $employee]);
    }

    public function store(Request $request, Attendance $attendance): RedirectResponse
    {
        abort_unless($attendance->employee_id === $request->user()->employee_id, 404);

        $validated = $request->validate([
            'requested_time_in' => ['nullable', 'date_format:H:i'],
            'requested_time_out' => ['nullable', 'date_format:H:i', 'after:requested_time_in'],
            'requested_status' => ['required', Rule::enum(AttendanceStatus::class)],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        AttendanceCorrectionRequest::create([
            ...$validated,
            'attendance_id' => $attendance->id,
            'employee_id' => $attendance->employee_id,
            'company_id' => $attendance->company_id,
            'status' => AttendanceCorrectionRequestStatus::Pending,
            'requested_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Correction request submitted.');
    }
}
