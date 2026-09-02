<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Competency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Competency>
 */
class CompetencyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->unique()->words(3, true),
            'is_active' => true,
        ];
    }
}
