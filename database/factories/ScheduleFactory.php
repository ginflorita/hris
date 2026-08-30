<?php

namespace Database\Factories;

use App\Enums\ScheduleType;
use App\Models\Company;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'shift_id' => null,
            'name' => 'Regular Weekday Schedule',
            'code' => fake()->unique()->regexify('SC[0-9]{2,4}'),
            'type' => ScheduleType::Fixed,
            'rest_days' => [0, 6],
            'is_active' => true,
        ];
    }
}
