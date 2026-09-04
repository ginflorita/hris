<?php

namespace Database\Factories;

use App\Enums\WorkflowApproverType;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowStep>
 */
class WorkflowStepFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workflow_definition_id' => WorkflowDefinition::factory(),
            'step_order' => 1,
            'name' => 'HR Approval',
            'approver_type' => WorkflowApproverType::Permission,
            'required_permission' => 'employees.update',
        ];
    }

    public function manager(): static
    {
        return $this->state([
            'name' => 'Manager Acknowledgment',
            'approver_type' => WorkflowApproverType::Manager,
            'required_permission' => null,
        ]);
    }
}
