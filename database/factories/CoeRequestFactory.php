<?php

namespace Database\Factories;

use App\Enums\CoeRequestStatus;
use App\Enums\CoeRequestType;
use App\Models\CoeRequest;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoeRequest>
 */
class CoeRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'company_id' => Company::factory(),
            'type' => CoeRequestType::Standard,
            'purpose' => fake()->sentence(3),
            'status' => CoeRequestStatus::Pending,
            'requested_by' => null,
        ];
    }

    public function forEmployee(Employee $employee): static
    {
        return $this->state([
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
        ]);
    }

    public function approved(): static
    {
        return $this->state([
            'status' => CoeRequestStatus::Approved,
            'approved_at' => now(),
            'snapshot_position' => 'Software Engineer',
            'snapshot_department' => 'Engineering',
            'snapshot_employment_status' => 'active',
            'snapshot_date_hired' => now()->subYears(2)->toDateString(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => CoeRequestStatus::Rejected,
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}
