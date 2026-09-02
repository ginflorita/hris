<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\TrainingProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingProvider>
 */
class TrainingProviderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->unique()->company(),
            'is_active' => true,
        ];
    }
}
