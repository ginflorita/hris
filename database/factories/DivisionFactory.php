<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Division>
 */
class DivisionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->randomElement(['Corporate Services', 'Operations', 'Technology', 'Commercial']).' Division',
            'code' => fake()->unique()->regexify('[A-Z]{2,4}'),
            'is_active' => true,
        ];
    }
}
