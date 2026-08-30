<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeDependent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeDependent>
 */
class EmployeeDependentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'name' => fake()->name(),
            'relationship' => fake()->randomElement(['Spouse', 'Child', 'Parent']),
            'birth_date' => fake()->dateTimeBetween('-70 years', '-1 years'),
            'is_beneficiary' => true,
        ];
    }
}
