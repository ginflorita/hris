<?php

namespace Database\Factories;

use App\Enums\ProficiencyLevel;
use App\Models\Competency;
use App\Models\Employee;
use App\Models\EmployeeCompetency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeCompetency>
 */
class EmployeeCompetencyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'competency_id' => Competency::factory(),
            'proficiency_level' => ProficiencyLevel::Intermediate,
        ];
    }

    public function forEmployee(Employee $employee): static
    {
        return $this->state(['employee_id' => $employee->id]);
    }
}
