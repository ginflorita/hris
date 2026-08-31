<?php

namespace Database\Factories;

use App\Enums\GovernmentAgency;
use App\Models\Company;
use App\Models\ContributionRateTable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContributionRateTable>
 */
class ContributionRateTableFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'agency' => fake()->randomElement(GovernmentAgency::cases()),
            'name' => fake()->year().' Contribution Table',
            'effective_from' => now()->startOfYear(),
            'effective_to' => null,
            'is_active' => true,
        ];
    }
}
