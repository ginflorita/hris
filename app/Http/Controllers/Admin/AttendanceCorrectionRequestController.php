<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Attendance\Services\AttendanceCorrectionService;
use App\Enums\AttendanceCorrectionRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrectionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AttendanceCorrectionRequestController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('attendance.correct');

        $query = AttendanceCorrectionRequest::with(['employee', 'attendance'])->orderByDesc('created_at');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return view('admin.attendance.correction-requests.index', [
            'correctionRequests' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only('status'),
        ]);
    }

    /**
     * Applies the exact same audit-logged correction as a direct HR
     * correction (AttendanceController::update()) -- see
     * AttendanceCorrectionService::apply(). The request row itself is
     * just the approval record; it never becomes the source of truth for
     * the attendance data.
     */
    public function approve(Request $request, AttendanceCorrectionRequest $correctionRequest, AttendanceCorrectionService $service): RedirectResponse
    {
        $this->authorize('attendance.correct');
        abort_unless($correctionRequest->status === AttendanceCorrectionRequestStatus::Pending, 422, 'Only a pending request can be approved.');

        $date = $correctionRequest->attendance->date->format('Y-m-d');
        $newTimeIn = $correctionRequest->requested_time_in ? Carbon::parse("{$date} {$correctionRequest->requested_time_in}") : null;
        $newTimeOut = $correctionRequest->requested_time_out ? Carbon::parse("{$date} {$correctionRequest->requested_time_out}") : null;

        $service->apply(
            $correctionRequest->attendance,
            $newTimeIn,
            $newTimeOut,
            $correctionRequest->requested_status,
            'Employee correction request #'.$correctionRequest->id.': '.$correctionRequest->reason,
            $request->user()->id,
        );

        $correctionRequest->update([
            'status' => AttendanceCorrectionRequestStatus::Approved,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('status', 'Correction request approved and applied.');
    }

    public function reject(Request $request, AttendanceCorrectionRequest $correctionRequest): RedirectResponse
    {
        $this->authorize('attendance.correct');
        abort_unless($correctionRequest->status === AttendanceCorrectionRequestStatus::Pending, 422, 'Only a pending request can be rejected.');

        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:500']]);

        $correctionRequest->update([
            'status' => AttendanceCorrectionRequestStatus::Rejected,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return back()->with('status', 'Correction request rejected.');
    }
}
