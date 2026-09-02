<?php

namespace Database\Factories;

use App\Enums\PerformanceImprovementPlanStatus;
use App\Models\Employee;
use App\Models\PerformanceImprovementPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerformanceImprovementPlan>
 */
class PerformanceImprovementPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'initiated_by' => Employee::factory(),
            'reason' => fake()->sentence(8),
            'goals' => fake()->paragraph(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => PerformanceImprovementPlanStatus::Active,
        ];
    }

    public function forEmployee(Employee $employee): static
    {
        return $this->state(['employee_id' => $employee->id]);
    }
}
