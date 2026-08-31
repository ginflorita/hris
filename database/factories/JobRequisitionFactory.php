<?php

namespace Database\Factories;

use App\Enums\JobRequisitionStatus;
use App\Models\Company;
use App\Models\JobRequisition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobRequisition>
 */
class JobRequisitionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'department_id' => null,
            'position_id' => null,
            'openings_count' => fake()->numberBetween(1, 3),
            'justification' => fake()->sentence(),
            'target_start_date' => fake()->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            'status' => JobRequisitionStatus::Pending,
            'requested_by' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state([
            'status' => JobRequisitionStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => JobRequisitionStatus::Rejected,
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}
