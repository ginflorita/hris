<?php

namespace Database\Factories;

use App\Enums\WorkflowInstanceStatus;
use App\Models\EmployeeInformationChangeRequest;
use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowInstance>
 *
 * Defaults its subject to an `EmployeeInformationChangeRequest` --
 * the only real subject this engine has as of Phase 20b -- rather
 * than a null/fake morph target, so a plain `WorkflowInstance::factory()
 * ->create()` is a genuinely valid, queryable row.
 */
class WorkflowInstanceFactory extends Factory
{
    public function definition(): array
    {
        $definition = WorkflowDefinition::factory()->create();
        $subject = EmployeeInformationChangeRequest::factory()->create();

        return [
            'workflow_definition_id' => $definition->id,
            'company_id' => $definition->company_id,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->id,
            'initiated_by' => User::factory(),
            'status' => WorkflowInstanceStatus::InProgress,
            'current_step_order' => 1,
        ];
    }
}
