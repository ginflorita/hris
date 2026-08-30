<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->randomElement(['Vacation Leave', 'Sick Leave', 'Emergency Leave', 'Bereavement Leave']),
            'code' => fake()->unique()->regexify('[A-Z]{2,4}'),
            'is_paid' => true,
            'max_days_per_year' => 15,
            'requires_approval' => true,
            'is_active' => true,
        ];
    }
}
