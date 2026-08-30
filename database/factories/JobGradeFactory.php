<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\JobGrade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobGrade>
 */
class JobGradeFactory extends Factory
{
    public function definition(): array
    {
        $rank = fake()->unique()->numberBetween(1, 20);

        return [
            'company_id' => Company::factory(),
            'name' => "Grade {$rank}",
            'code' => "G{$rank}",
            'rank' => $rank,
            'is_active' => true,
        ];
    }
}
