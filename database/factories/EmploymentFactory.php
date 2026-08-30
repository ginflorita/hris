<?php

namespace Database\Factories;

use App\Enums\EmploymentChangeType;
use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Employment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employment>
 */
class EmploymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'company_id' => Company::factory(),
            'employment_type' => EmploymentType::Probationary,
            'work_arrangement' => null,
            'status' => EmploymentStatus::Active,
            'change_type' => EmploymentChangeType::Hire,
            'basic_salary' => fake()->numberBetween(20000, 80000),
            'effective_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'end_date' => null,
        ];
    }

    /**
     * Attach to an employee, inheriting that employee's company.
     */
    public function forEmployee(Employee $employee): static
    {
        return $this->state([
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
        ]);
    }
}
