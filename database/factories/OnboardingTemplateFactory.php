<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\OnboardingTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OnboardingTemplate>
 */
class OnboardingTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'New Employee Onboarding',
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
