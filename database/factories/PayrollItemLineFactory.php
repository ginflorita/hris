<?php

namespace Database\Factories;

use App\Enums\PayrollItemLineType;
use App\Models\PayrollItem;
use App\Models\PayrollItemLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollItemLine>
 */
class PayrollItemLineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payroll_item_id' => PayrollItem::factory(),
            'type' => PayrollItemLineType::Earning,
            'category' => 'basic_salary',
            'label' => 'Basic Pay',
            'amount' => 30000,
            'is_adjustment' => false,
        ];
    }
}
