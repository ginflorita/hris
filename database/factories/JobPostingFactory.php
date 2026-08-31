<?php

namespace Database\Factories;

use App\Enums\JobPostingStatus;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\JobRequisition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobPosting>
 */
class JobPostingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'job_requisition_id' => JobRequisition::factory()->approved(),
            'company_id' => Company::factory(),
            'title' => fake()->jobTitle(),
            'description' => fake()->paragraph(),
            'is_internal' => false,
            'status' => JobPostingStatus::Draft,
        ];
    }

    public function forRequisition(JobRequisition $requisition): static
    {
        return $this->state([
            'job_requisition_id' => $requisition->id,
            'company_id' => $requisition->company_id,
        ]);
    }

    public function published(): static
    {
        return $this->state([
            'status' => JobPostingStatus::Published,
            'published_at' => now(),
        ]);
    }
}
