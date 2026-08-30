<?php

namespace Database\Factories;

use App\Enums\GovernmentIdType;
use App\Models\Employee;
use App\Models\EmployeeGovernmentId;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeGovernmentId>
 */
class EmployeeGovernmentIdFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'id_type' => GovernmentIdType::SSS,
            'id_number' => fake()->numerify('##-#######-#'),
            'issued_at' => fake()->optional()->date(),
            'expires_at' => null,
        ];
    }
}
