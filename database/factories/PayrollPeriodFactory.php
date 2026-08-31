<?php

namespace Database\Factories;

use App\Enums\PayrollPeriodStatus;
use App\Models\Company;
use App\Models\PayrollGroup;
use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollPeriod>
 */
class PayrollPeriodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'payroll_group_id' => PayrollGroup::factory(),
            'name' => 'January 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'pay_date' => '2026-02-05',
            'status' => PayrollPeriodStatus::Draft,
        ];
    }
}
