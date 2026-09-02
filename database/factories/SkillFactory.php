<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Skill>
 */
class SkillFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->unique()->words(2, true),
            'is_active' => true,
        ];
    }
}
