<?php

namespace Database\Factories;

use App\Enums\AccrualFrequency;
use App\Models\Company;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeavePolicy>
 */
class LeavePolicyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'leave_type_id' => LeaveType::factory(),
            'name' => 'Standard Accrual',
            'accrual_rate' => 1.25,
            'accrual_frequency' => AccrualFrequency::Monthly,
            'max_balance' => 30,
            'carry_over_days' => 5,
            'is_active' => true,
        ];
    }
}
