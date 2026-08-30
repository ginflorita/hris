<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'department_id' => null,
            'job_level_id' => null,
            'job_grade_id' => null,
            'title' => fake()->unique()->jobTitle(),
            'code' => fake()->unique()->regexify('POS[0-9]{3,4}'),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
