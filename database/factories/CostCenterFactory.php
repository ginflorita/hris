<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CostCenter>
 */
class CostCenterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'department_id' => null,
            'name' => fake()->unique()->words(2, true).' Cost Center',
            'code' => fake()->unique()->regexify('CC[0-9]{3,4}'),
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
