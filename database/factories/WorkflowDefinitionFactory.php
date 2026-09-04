<?php

namespace Database\Factories;

use App\Enums\WorkflowProcessType;
use App\Models\Company;
use App\Models\WorkflowDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowDefinition>
 */
class WorkflowDefinitionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->unique()->words(3, true),
            'process_type' => WorkflowProcessType::EmployeeInformationChange,
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
