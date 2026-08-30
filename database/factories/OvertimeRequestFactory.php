<?php

namespace Database\Factories;

use App\Enums\OvertimeStatus;
use App\Models\Company;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OvertimeRequest>
 */
class OvertimeRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'company_id' => Company::factory(),
            'date' => fake()->dateTimeBetween('-30 days', 'now'),
            'requested_hours' => fake()->randomFloat(2, 1, 4),
            'reason' => fake()->sentence(),
            'status' => OvertimeStatus::Pending,
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
