<?php

namespace Database\Factories;

use App\Enums\WorkflowApproverType;
use App\Enums\WorkflowInstanceStepStatus;
use App\Models\WorkflowInstance;
use App\Models\WorkflowInstanceStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowInstanceStep>
 */
class WorkflowInstanceStepFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workflow_instance_id' => WorkflowInstance::factory(),
            'workflow_step_id' => null,
            'step_order' => 1,
            'name' => 'HR Approval',
            'approver_type' => WorkflowApproverType::Permission,
            'required_permission' => 'employees.update',
            'status' => WorkflowInstanceStepStatus::Pending,
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

    public function forInstance(WorkflowInstance $instance): static
    {
        return $this->state([
            'workflow_instance_id' => $instance->id,
        ]);
    }
}
