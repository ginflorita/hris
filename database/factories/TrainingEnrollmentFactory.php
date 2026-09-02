<?php

namespace Database\Factories;

use App\Enums\TrainingEnrollmentStatus;
use App\Models\Employee;
use App\Models\TrainingEnrollment;
use App\Models\TrainingSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingEnrollment>
 */
class TrainingEnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'training_session_id' => TrainingSession::factory(),
            'status' => TrainingEnrollmentStatus::Enrolled,
            'enrolled_at' => now(),
        ];
    }

    public function forEmployee(Employee $employee): static
    {
        return $this->state(['employee_id' => $employee->id]);
    }

    public function forSession(TrainingSession $session): static
    {
        return $this->state(['training_session_id' => $session->id]);
    }
}
