<?php

namespace Database\Factories;

use App\Models\EmployeeOnboarding;
use App\Models\EmployeeOnboardingTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeOnboardingTask>
 */
class EmployeeOnboardingTaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_onboarding_id' => EmployeeOnboarding::factory(),
            'title' => fake()->sentence(3),
            'description' => null,
            'sequence' => 0,
            'is_completed' => false,
            'completed_at' => null,
            'completed_by' => null,
        ];
    }

    public function forOnboarding(EmployeeOnboarding $onboarding): static
    {
        return $this->state(['employee_onboarding_id' => $onboarding->id]);
    }

    public function completed(): static
    {
        return $this->state([
            'is_completed' => true,
            'completed_at' => now(),
        ]);
    }
}
