<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Attendance\Services\AttendanceCorrectionService;
use App\Enums\AttendanceSource;
use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

    public function store(Request $request, AttendanceCorrectionService $service): RedirectResponse
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

        [$lateMinutes, $undertimeMinutes] = $service->computeMinutes($employee, $timeIn, $timeOut);

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
     * Corrections are logged, never silent -- see
     * App\Domain\Attendance\Services\AttendanceCorrectionService::apply(),
     * the one place an Attendance row's time_in/time_out/status change
     * after creation.
     */
    public function update(Request $request, Attendance $attendance, AttendanceCorrectionService $service): RedirectResponse
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

        $service->apply(
            $attendance,
            $newTimeIn,
            $newTimeOut,
            AttendanceStatus::from($validated['status']),
            $validated['reason'],
            $request->user()->id,
        );

        return redirect()->route('admin.attendance.attendances.index')->with('status', 'Attendance corrected.');
    }
}
