<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeOnboarding;
use App\Models\OnboardingTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeOnboarding>
 */
class EmployeeOnboardingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'onboarding_template_id' => OnboardingTemplate::factory(),
            'assigned_by' => null,
            'notes' => null,
        ];
    }

    public function forEmployee(Employee $employee): static
    {
        return $this->state(['employee_id' => $employee->id]);
    }
}
