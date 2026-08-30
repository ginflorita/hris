<?php

namespace Database\Factories;

use App\Enums\CompensationFrequency;
use App\Enums\CompensationItemType;
use App\Models\Company;
use App\Models\CompensationItem;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompensationItem>
 */
class CompensationItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'company_id' => Company::factory(),
            'type' => CompensationItemType::Allowance,
            'name' => 'Transportation Allowance',
            'amount' => 2000,
            'frequency' => CompensationFrequency::Monthly,
            'effective_date' => now()->toDateString(),
            'is_active' => true,
        ];
    }

    public function forEmployee(Employee $employee): static
    {
        return $this->state([
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
        ]);
    }
}
