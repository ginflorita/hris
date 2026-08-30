<?php

namespace Database\Factories;

use App\Enums\AttendanceSource;
use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d');

        return [
            'employee_id' => Employee::factory(),
            'company_id' => Company::factory(),
            'date' => $date,
            'time_in' => $date.' 08:00:00',
            'time_out' => $date.' 17:00:00',
            'break_start' => $date.' 12:00:00',
            'break_end' => $date.' 13:00:00',
            'source' => AttendanceSource::Manual,
            'status' => AttendanceStatus::Present,
            'late_minutes' => 0,
            'undertime_minutes' => 0,
            'overtime_minutes' => 0,
        ];
    }

    public function forEmployee(Employee $employee): static
    {
        return $this->state([
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
        ]);
    }
}
