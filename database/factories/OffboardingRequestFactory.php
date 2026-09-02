<?php

namespace Database\Factories;

use App\Enums\OffboardingStatus;
use App\Models\Employee;
use App\Models\OffboardingRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OffboardingRequest>
 */
class OffboardingRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'resignation_date' => '2026-01-31',
            'status' => OffboardingStatus::Resignation,
            'status_changed_at' => now(),
        ];
    }

    public function forEmployee(Employee $employee): static
    {
        return $this->state(['employee_id' => $employee->id]);
    }
}
