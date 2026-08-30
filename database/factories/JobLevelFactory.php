<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\JobLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobLevel>
 */
class JobLevelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->unique()->randomElement(['Entry', 'Junior', 'Intermediate', 'Senior', 'Lead', 'Principal']),
            'code' => fake()->unique()->regexify('L[0-9]{1,2}'),
            'rank' => fake()->unique()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }
}
