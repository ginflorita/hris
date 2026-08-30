<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'department_id' => null,
            'name' => fake()->unique()->words(2, true).' Section',
            'code' => fake()->unique()->regexify('[A-Z]{2,5}'),
            'is_active' => true,
        ];
    }

    /**
     * Attach to a department, inheriting that department's company.
     */
    public function inDepartment(Department $department): static
    {
        return $this->state([
            'company_id' => $department->company_id,
            'department_id' => $department->id,
        ]);
    }
}
