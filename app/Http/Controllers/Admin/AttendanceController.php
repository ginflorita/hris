<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttendanceSource;
use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('attendance.view');

        $query = Attendance::with('employee')->orderByDesc('date');

        if ($employeeId = $request->integer('employee_id')) {
            $query->where('employee_id', $employeeId);
        }
        if ($dateFrom = $request->date('date_from')) {
            $query->whereDate('date', '>=', $dateFrom);
        }
        if ($dateTo = $request->date('date_to')) {
            $query->whereDate('date', '<=', $dateTo);
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return view('admin.attendance.attendances.index', [
            'attendances' => $query->paginate(25)->withQueryString(),
            'employees' => Employee::orderBy('last_name')->get(),
            'filters' => $request->only(['employee_id', 'date_from', 'date_to', 'status']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('attendance.manage');

        return view('admin.attendance.attendances.create', ['employees' => Employee::orderBy('last_name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('attendance.manage');

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'date' => [
                'required', 'date',
                Rule::unique('attendances', 'date')->where('employee_id', $request->input('employee_id')),
            ],
            'time_in' => ['nullable', 'date_format:H:i'],
            'time_out' => ['nullable', 'date_format:H:i', 'after:time_in'],
            'status' => ['required', Rule::enum(AttendanceStatus::class)],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $date = $validated['date'];

        $timeIn = $validated['time_in'] ? Carbon::parse("{$date} {$validated['time_in']}") : null;
        $timeOut = $validated['time_out'] ? Carbon::parse("{$date} {$validated['time_out']}") : null;

        [$lateMinutes, $undertimeMinutes] = $this->computeMinutes($employee, $timeIn, $timeOut);

        Attendance::create([
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'date' => $date,
            'time_in' => $timeIn,
            'time_out' => $timeOut,
            'source' => AttendanceSource::Manual,
            'status' => $validated['status'],
            'late_minutes' => $lateMinutes,
            'undertime_minutes' => $undertimeMinutes,
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return redirect()->route('admin.attendance.attendances.index')->with('status', 'Attendance recorded.');
    }

    public function edit(Attendance $attendance): View
    {
        $this->authorize('attendance.correct');

        return view('admin.attendance.attendances.edit', ['attendance' => $attendance]);
    }

    /**
     * Corrections are logged, never silent — every changed field is
     * written to attendance_correction_logs with old/new values and the
     * given reason before the attendance row itself is updated. Never
     * deleted/replaced (there's one row per employee per day), only
     * corrected in place with an audit trail.
     */
    public function update(Request $request, Attendance $attendance): RedirectResponse
    {
        $this->authorize('attendance.correct');

        $validated = $request->validate([
            'time_in' => ['nullable', 'date_format:H:i'],
            'time_out' => ['nullable', 'date_format:H:i', 'after:time_in'],
            'status' => ['required', Rule::enum(AttendanceStatus::class)],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $date = $attendance->date->format('Y-m-d');
        $newTimeIn = $validated['time_in'] ? Carbon::parse("{$date} {$validated['time_in']}") : null;
        $newTimeOut = $validated['time_out'] ? Carbon::parse("{$date} {$validated['time_out']}") : null;

        [$lateMinutes, $undertimeMinutes] = $this->computeMinutes($attendance->employee, $newTimeIn, $newTimeOut);

        $fieldChanges = [
            'time_in' => [$attendance->time_in?->format('H:i'), $newTimeIn?->format('H:i')],
            'time_out' => [$attendance->time_out?->format('H:i'), $newTimeOut?->format('H:i')],
            'status' => [$attendance->status->value, $validated['status']],
        ];

        DB::transaction(function () use ($attendance, $fieldChanges, $validated, $newTimeIn, $newTimeOut, $lateMinutes, $undertimeMinutes, $request) {
            foreach ($fieldChanges as $field => [$old, $new]) {
                if ($old !== $new) {
                    $attendance->correctionLogs()->create([
                        'field' => $field,
                        'old_value' => $old,
                        'new_value' => $new,
                        'reason' => $validated['reason'],
                        'corrected_by' => $request->user()->id,
                    ]);
                }
            }

            $attendance->update([
                'time_in' => $newTimeIn,
                'time_out' => $newTimeOut,
                'status' => $validated['status'],
                'late_minutes' => $lateMinutes,
                'undertime_minutes' => $undertimeMinutes,
                'is_corrected' => true,
                'corrected_by' => $request->user()->id,
                'corrected_at' => now(),
            ]);
        });

        return redirect()->route('admin.attendance.attendances.index')->with('status', 'Attendance corrected.');
    }

    /**
     * @return array{0: int, 1: int} [late_minutes, undertime_minutes]
     */
    private function computeMinutes(Employee $employee, ?Carbon $timeIn, ?Carbon $timeOut): array
    {
        $shift = $employee->currentSchedule?->schedule?->shift;

        if (! $shift || ! $timeIn) {
            return [0, 0];
        }

        $date = $timeIn->format('Y-m-d');
        $shiftStart = Carbon::parse("{$date} {$shift->start_time}")->addMinutes($shift->grace_minutes);
        $lateMinutes = $timeIn->greaterThan($shiftStart) ? $timeIn->diffInMinutes($shiftStart, true) : 0;

        $undertimeMinutes = 0;
        if ($timeOut) {
            $shiftEnd = Carbon::parse("{$date} {$shift->end_time}");
            $undertimeMinutes = $timeOut->lessThan($shiftEnd) ? $shiftEnd->diffInMinutes($timeOut, true) : 0;
        }

        return [$lateMinutes, $undertimeMinutes];
    }
}
