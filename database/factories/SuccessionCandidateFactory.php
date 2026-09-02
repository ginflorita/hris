<?php

namespace Database\Factories;

use App\Enums\SuccessionReadiness;
use App\Models\Employee;
use App\Models\Position;
use App\Models\SuccessionCandidate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SuccessionCandidate>
 */
class SuccessionCandidateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'position_id' => Position::factory(),
            'readiness' => SuccessionReadiness::Ready1To2Years,
        ];
    }

    public function forEmployee(Employee $employee): static
    {
        return $this->state(['employee_id' => $employee->id]);
    }
}
