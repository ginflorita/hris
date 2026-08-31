<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Blueprint §41's portal sidebar lists "Requests" as one flat item
 * alongside the type-specific My Leave/My Overtime/My Attendance/Request
 * COE pages -- a single at-a-glance view across all four, not a
 * replacement for them. Each row links back to its own page for the
 * actual submit/cancel actions; this controller only reads and merges.
 */
class RequestController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return view('portal.requests.index', ['employee' => null, 'requests' => collect()]);
        }

        $employee->load([
            'leaveRequests.leaveType',
            'overtimeRequests',
            'attendanceCorrectionRequests.attendance',
            'coeRequests',
        ]);

        $requests = collect()
            ->concat($employee->leaveRequests->map(fn ($r) => (object) [
                'type' => 'Leave',
                'date' => $r->start_date,
                'detail' => $r->leaveType->name.' — '.number_format((float) $r->days_count, 2).' day(s)',
                'status' => $r->status->value,
                'link' => route('portal.leave.index'),
            ]))
            ->concat($employee->overtimeRequests->map(fn ($r) => (object) [
                'type' => 'Overtime',
                'date' => $r->date,
                'detail' => number_format((float) $r->requested_hours, 2).' hour(s)',
                'status' => $r->status->value,
                'link' => route('portal.overtime.index'),
            ]))
            ->concat($employee->attendanceCorrectionRequests->map(fn ($r) => (object) [
                'type' => 'Attendance Correction',
                'date' => $r->attendance->date,
                'detail' => 'Requested: '.ucwords(str_replace('_', ' ', $r->requested_status->value)),
                'status' => $r->status->value,
                'link' => route('portal.attendance.index'),
            ]))
            ->concat($employee->coeRequests->map(fn ($r) => (object) [
                'type' => 'Certificate of Employment',
                'date' => $r->created_at,
                'detail' => $r->type->label(),
                'status' => $r->status->value,
                'link' => route('portal.coe.index'),
            ]))
            ->sortByDesc('date')
            ->values();

        return view('portal.requests.index', ['employee' => $employee, 'requests' => $requests]);
    }
}
