<?php

namespace Database\Factories;

use App\Models\ContributionRateBracket;
use App\Models\ContributionRateTable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContributionRateBracket>
 */
class ContributionRateBracketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'contribution_rate_table_id' => ContributionRateTable::factory(),
            'order' => 0,
            'min_salary' => 0,
            'max_salary' => 10000,
            'employee_amount' => 100,
            'employer_amount' => 200,
        ];
    }
}
