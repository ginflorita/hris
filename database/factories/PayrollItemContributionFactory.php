<?php

namespace Database\Factories;

use App\Enums\GovernmentAgency;
use App\Models\ContributionRateBracket;
use App\Models\ContributionRateTable;
use App\Models\PayrollItem;
use App\Models\PayrollItemContribution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollItemContribution>
 */
class PayrollItemContributionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payroll_item_id' => PayrollItem::factory(),
            'contribution_rate_table_id' => ContributionRateTable::factory(),
            'contribution_rate_bracket_id' => ContributionRateBracket::factory(),
            'agency' => GovernmentAgency::SSS,
            'employee_amount' => 180,
            'employer_amount' => 380,
        ];
    }
}
