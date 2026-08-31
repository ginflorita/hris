<?php

namespace Database\Factories;

use App\Enums\InterviewStatus;
use App\Enums\InterviewType;
use App\Models\Application;
use App\Models\Interview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Interview>
 */
class InterviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'interviewer_id' => null,
            'type' => InterviewType::PhoneScreen,
            'scheduled_at' => now()->addDays(3),
            'location' => null,
            'status' => InterviewStatus::Scheduled,
        ];
    }

    public function forApplication(Application $application): static
    {
        return $this->state(['application_id' => $application->id]);
    }
}
