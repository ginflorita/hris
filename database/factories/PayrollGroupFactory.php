<?php

namespace Database\Factories;

use App\Enums\PayFrequency;
use App\Models\Company;
use App\Models\PayrollGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollGroup>
 */
class PayrollGroupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->randomElement(['Monthly - Head Office', 'Semi-Monthly - Rank and File']),
            'code' => fake()->unique()->regexify('PG[0-9]{2,4}'),
            'pay_frequency' => fake()->randomElement(PayFrequency::cases()),
            'is_active' => true,
        ];
    }
}
