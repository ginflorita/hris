<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'applicant_id' => Applicant::factory(),
            'job_posting_id' => JobPosting::factory()->published(),
            'status' => ApplicationStatus::Applied,
            'applied_at' => now(),
        ];
    }

    public function between(Applicant $applicant, JobPosting $posting): static
    {
        return $this->state([
            'applicant_id' => $applicant->id,
            'job_posting_id' => $posting->id,
        ]);
    }
}
