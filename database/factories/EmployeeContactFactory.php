<?php

namespace Database\Factories;

use App\Enums\ContactType;
use App\Models\Employee;
use App\Models\EmployeeContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeContact>
 */
class EmployeeContactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'type' => ContactType::Mobile,
            'value' => fake()->phoneNumber(),
            'label' => null,
            'is_primary' => true,
        ];
    }
}
