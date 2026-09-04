<?php

namespace App\Domain\Workflow\Contracts;

use App\Models\Employee;

/**
 * Implemented by any model that can go through the workflow engine as
 * a `WorkflowInstance` subject and needs a `Manager`-type step
 * resolved -- the engine needs to know *which* employee's manager can
 * act, without needing to know anything else about the subject.
 */
interface HasWorkflowSubjectEmployee
{
    public function workflowEmployee(): Employee;
}
