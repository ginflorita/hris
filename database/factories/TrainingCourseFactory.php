<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\TrainingCourse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingCourse>
 */
class TrainingCourseFactory extends Factory
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
