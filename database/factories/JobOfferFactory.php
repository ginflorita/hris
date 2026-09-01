<?php

namespace Database\Factories;

use App\Enums\EmploymentType;
use App\Enums\JobOfferStatus;
use App\Enums\WorkArrangement;
use App\Models\Application;
use App\Models\JobOffer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobOffer>
 */
class JobOfferFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'department_id' => null,
            'position_id' => null,
            'employment_type' => EmploymentType::Regular,
            'work_arrangement' => WorkArrangement::Onsite,
            'offered_salary' => fake()->numberBetween(20000, 80000),
            'start_date' => now()->addWeeks(2)->toDateString(),
            'expires_at' => now()->addWeek()->toDateString(),
            'notes' => null,
            'status' => JobOfferStatus::Pending,
            'extended_by' => null,
        ];
    }

    public function forApplication(Application $application): static
    {
        return $this->state(['application_id' => $application->id]);
    }

    public function accepted(): static
    {
        return $this->state([
            'status' => JobOfferStatus::Accepted,
            'responded_at' => now(),
        ]);
    }

    public function declined(): static
    {
        return $this->state([
            'status' => JobOfferStatus::Declined,
            'responded_at' => now(),
            'decision_reason' => fake()->sentence(),
        ]);
    }
}
