<?php

namespace Database\Factories;

use App\Enums\ProficiencyLevel;
use App\Models\Employee;
use App\Models\EmployeeSkill;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeSkill>
 */
class EmployeeSkillFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'skill_id' => Skill::factory(),
            'proficiency_level' => ProficiencyLevel::Intermediate,
        ];
    }

    public function forEmployee(Employee $employee): static
    {
        return $this->state(['employee_id' => $employee->id]);
    }
}
