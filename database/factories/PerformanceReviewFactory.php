<?php

namespace Database\Factories;

use App\Enums\PerformanceReviewStatus;
use App\Enums\PerformanceReviewType;
use App\Models\Employee;
use App\Models\PerformanceCycle;
use App\Models\PerformanceReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerformanceReview>
 */
class PerformanceReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'performance_cycle_id' => PerformanceCycle::factory(),
            'reviewer_id' => Employee::factory(),
            'type' => PerformanceReviewType::Peer,
            'status' => PerformanceReviewStatus::Draft,
        ];
    }

    public function forEmployee(Employee $employee): static
    {
        return $this->state(['employee_id' => $employee->id]);
    }
}
