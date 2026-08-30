<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\SalaryStructure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalaryStructure>
 */
class SalaryStructureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->year().' Salary Structure',
            'code' => fake()->unique()->regexify('SS[0-9]{2,4}'),
            'effective_date' => now()->startOfYear(),
            'is_active' => true,
        ];
    }
}
