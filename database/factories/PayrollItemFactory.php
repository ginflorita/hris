<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollItem>
 */
class PayrollItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payroll_period_id' => PayrollPeriod::factory(),
            'employee_id' => Employee::factory(),
            'company_id' => Company::factory(),
            'basic_salary' => 30000,
            'gross_earnings' => 30000,
            'total_employee_contributions' => 0,
            'total_employer_contributions' => 0,
            'tax_amount' => 0,
            'total_deductions' => 0,
            'net_pay' => 30000,
            'computed_at' => now(),
        ];
    }
}
