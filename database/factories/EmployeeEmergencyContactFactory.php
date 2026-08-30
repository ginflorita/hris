<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeEmergencyContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeEmergencyContact>
 */
class EmployeeEmergencyContactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'name' => fake()->name(),
            'relationship' => fake()->randomElement(['Spouse', 'Parent', 'Sibling', 'Friend']),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'is_primary' => true,
        ];
    }
}
