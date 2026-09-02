<?php

namespace Database\Factories;

use App\Enums\CareerDevelopmentPlanStatus;
use App\Models\CareerDevelopmentPlan;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareerDevelopmentPlan>
 */
class CareerDevelopmentPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'development_actions' => fake()->paragraph(),
            'status' => CareerDevelopmentPlanStatus::Active,
        ];
    }

    public function forEmployee(Employee $employee): static
    {
        return $this->state(['employee_id' => $employee->id]);
    }
}
