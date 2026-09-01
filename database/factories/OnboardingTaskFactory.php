<?php

namespace Database\Factories;

use App\Models\OnboardingTask;
use App\Models\OnboardingTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OnboardingTask>
 */
class OnboardingTaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'onboarding_template_id' => OnboardingTemplate::factory(),
            'title' => fake()->randomElement(['Submit requirements', 'Sign contract', 'Orientation', 'Company ID', 'Equipment', 'System account']),
            'description' => null,
            'sequence' => 0,
        ];
    }

    public function forTemplate(OnboardingTemplate $template): static
    {
        return $this->state(['onboarding_template_id' => $template->id]);
    }
}
