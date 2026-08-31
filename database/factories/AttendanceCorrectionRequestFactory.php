<?php

namespace Database\Factories;

use App\Enums\AttendanceCorrectionRequestStatus;
use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceCorrectionRequest>
 */
class AttendanceCorrectionRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'employee_id' => Employee::factory(),
            'company_id' => Company::factory(),
            'requested_time_in' => '08:00',
            'requested_time_out' => '17:00',
            'requested_status' => AttendanceStatus::Present,
            'reason' => fake()->sentence(),
            'status' => AttendanceCorrectionRequestStatus::Pending,
            'requested_by' => null,
        ];
    }

    public function forAttendance(Attendance $attendance): static
    {
        return $this->state([
            'attendance_id' => $attendance->id,
            'employee_id' => $attendance->employee_id,
            'company_id' => $attendance->company_id,
        ]);
    }

    public function approved(): static
    {
        return $this->state([
            'status' => AttendanceCorrectionRequestStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => AttendanceCorrectionRequestStatus::Rejected,
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}
