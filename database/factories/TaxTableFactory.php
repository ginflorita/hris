<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\TaxTable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxTable>
 */
class TaxTableFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->year().' Withholding Tax Table',
            'effective_from' => now()->startOfYear(),
            'effective_to' => null,
            'is_active' => true,
        ];
    }
}
