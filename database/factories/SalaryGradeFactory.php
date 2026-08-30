<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\SalaryGrade;
use App\Models\SalaryStructure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalaryGrade>
 */
class SalaryGradeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'salary_structure_id' => SalaryStructure::factory(),
            'name' => 'Grade '.fake()->numberBetween(1, 15),
            'code' => fake()->unique()->regexify('SG[0-9]{2,4}'),
            'min_salary' => 20000,
            'mid_salary' => 30000,
            'max_salary' => 40000,
            'is_active' => true,
        ];
    }
}
