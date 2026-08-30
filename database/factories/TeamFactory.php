<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use App\Models\Section;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'department_id' => null,
            'section_id' => null,
            'name' => fake()->unique()->words(2, true).' Team',
            'code' => fake()->unique()->regexify('[A-Z]{2,5}'),
            'is_active' => true,
        ];
    }

    /**
     * Attach directly to a department, inheriting its company.
     */
    public function inDepartment(Department $department): static
    {
        return $this->state([
            'company_id' => $department->company_id,
            'department_id' => $department->id,
        ]);
    }

    /**
     * Attach to a section, inheriting that section's company.
     */
    public function inSection(Section $section): static
    {
        return $this->state([
            'company_id' => $section->company_id,
            'section_id' => $section->id,
        ]);
    }
}
