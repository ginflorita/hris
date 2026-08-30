<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeNote>
 */
class EmployeeNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'note' => fake()->sentence(),
            'is_confidential' => true,
            'created_by' => null,
        ];
    }
}
