<?php

namespace Database\Factories;

use App\Enums\HolidayType;
use App\Models\Company;
use App\Models\Holiday;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Holiday>
 */
class HolidayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->unique()->words(2, true),
            'date' => fake()->unique()->dateTimeBetween('now', '+1 year'),
            'type' => fake()->randomElement(HolidayType::cases()),
            'is_active' => true,
        ];
    }
}
