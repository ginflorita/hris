<?php

namespace Database\Factories;

use App\Enums\BenefitType;
use App\Models\BenefitPlan;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BenefitPlan>
 */
class BenefitPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->unique()->words(2, true),
            'type' => BenefitType::Hmo,
            'is_active' => true,
        ];
    }
}
