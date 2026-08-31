<?php

namespace Database\Factories;

use App\Models\TaxTable;
use App\Models\TaxTableBracket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxTableBracket>
 */
class TaxTableBracketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tax_table_id' => TaxTable::factory(),
            'order' => 0,
            'min_income' => 0,
            'max_income' => 20000,
            'base_tax' => 0,
            'excess_rate_percent' => 0,
        ];
    }
}
