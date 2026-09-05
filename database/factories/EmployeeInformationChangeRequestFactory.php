<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeInformationChangeRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeInformationChangeRequest>
 */
class EmployeeInformationChangeRequestFactory extends Factory
{
    public function definition(): array
    {
        $employee = Employee::factory()->create();

        return [
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'requested_mobile' => fake()->phoneNumber(),
            'reason' => fake()->sentence(),
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
