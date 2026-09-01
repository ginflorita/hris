<?php

namespace Database\Factories;

use App\Enums\PerformanceCycleStatus;
use App\Models\Company;
use App\Models\PerformanceCycle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerformanceCycle>
 */
class PerformanceCycleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->year().' Annual Review',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => PerformanceCycleStatus::Draft,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => PerformanceCycleStatus::Active]);
    }
}
