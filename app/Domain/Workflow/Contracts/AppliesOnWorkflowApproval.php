<?php

namespace App\Domain\Workflow\Contracts;

/**
 * Implemented by a `WorkflowInstance` subject that has something to do
 * once every step approves -- e.g. `EmployeeInformationChangeRequest`
 * writes its requested fields onto the `Employee` record. A subject
 * that doesn't implement this (nothing to apply, a pure record-keeping
 * approval) is left alone by the engine once approved.
 */
interface AppliesOnWorkflowApproval
{
    public function applyWorkflowApproval(): void;
}
