<?php

namespace App\Domain\Attendance\Services;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The one place an Attendance row's time_in/time_out/status are ever
 * changed after creation -- every changed field is logged to
 * attendance_correction_logs (old/new value + reason) before the row
 * itself is updated. Both Admin\AttendanceController::update() (an HR
 * user correcting directly) and Admin\AttendanceCorrectionRequestController
 * ::approve() (an employee's self-service request being accepted) go
 * through apply(), so the audit trail is complete regardless of which
 * path a correction came from. computeMinutes() also lives here (not
 * just in apply()) because AttendanceController::store() needs the same
 * late/undertime math for a brand-new row, not just a correction.
 */
class AttendanceCorrectionService
{
    public function apply(
        Attendance $attendance,
        ?Carbon $newTimeIn,
        ?Carbon $newTimeOut,
        AttendanceStatus $newStatus,
        string $reason,
        int $correctedByUserId,
    ): void {
        [$lateMinutes, $undertimeMinutes] = $this->computeMinutes($attendance->employee, $newTimeIn, $newTimeOut);

        $fieldChanges = [
            'time_in' => [$attendance->time_in?->format('H:i'), $newTimeIn?->format('H:i')],
            'time_out' => [$attendance->time_out?->format('H:i'), $newTimeOut?->format('H:i')],
            'status' => [$attendance->status->value, $newStatus->value],
        ];

        DB::transaction(function () use ($attendance, $fieldChanges, $newTimeIn, $newTimeOut, $newStatus, $lateMinutes, $undertimeMinutes, $reason, $correctedByUserId) {
            foreach ($fieldChanges as $field => [$old, $new]) {
                if ($old !== $new) {
                    $attendance->correctionLogs()->create([
                        'field' => $field,
                        'old_value' => $old,
                        'new_value' => $new,
                        'reason' => $reason,
                        'corrected_by' => $correctedByUserId,
                    ]);
                }
            }

            $attendance->update([
                'time_in' => $newTimeIn,
                'time_out' => $newTimeOut,
                'status' => $newStatus,
                'late_minutes' => $lateMinutes,
                'undertime_minutes' => $undertimeMinutes,
                'is_corrected' => true,
                'corrected_by' => $correctedByUserId,
                'corrected_at' => now(),
            ]);
        });
    }

    /**
     * @return array{0: int, 1: int} [late_minutes, undertime_minutes]
     */
    public function computeMinutes(Employee $employee, ?Carbon $timeIn, ?Carbon $timeOut): array
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
