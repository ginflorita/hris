<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'division_id' => null,
            'name' => fake()->unique()->randomElement(['Human Resources', 'Finance', 'IT', 'Marketing', 'Sales', 'Legal', 'Operations']),
            'code' => fake()->unique()->regexify('[A-Z]{2,5}'),
            'is_active' => true,
        ];
    }

    /**
     * Attach to a division, inheriting that division's company rather
     * than generating a mismatched one of its own.
     */
    public function inDivision(Division $division): static
    {
        return $this->state([
            'company_id' => $division->company_id,
            'division_id' => $division->id,
        ]);
    }
}
