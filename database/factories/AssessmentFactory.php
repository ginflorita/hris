<?php

namespace Database\Factories;

use App\Enums\AssessmentType;
use App\Models\Application;
use App\Models\Assessment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assessment>
 */
class AssessmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'type' => AssessmentType::Technical,
            'due_at' => now()->addWeek()->toDateString(),
        ];
    }

    public function forApplication(Application $application): static
    {
        return $this->state(['application_id' => $application->id]);
    }
}
