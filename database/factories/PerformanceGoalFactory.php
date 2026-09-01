<?php

namespace Database\Factories;

use App\Enums\PerformanceGoalStatus;
use App\Models\Employee;
use App\Models\PerformanceCycle;
use App\Models\PerformanceGoal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerformanceGoal>
 */
class PerformanceGoalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'performance_cycle_id' => PerformanceCycle::factory(),
            'title' => fake()->sentence(4),
            'status' => PerformanceGoalStatus::NotStarted,
        ];
    }

    public function forEmployee(Employee $employee): static
    {
        return $this->state(['employee_id' => $employee->id]);
    }
}
