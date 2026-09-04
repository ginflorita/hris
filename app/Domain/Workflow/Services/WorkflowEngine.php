<?php

namespace App\Domain\Workflow\Services;

use App\Domain\Workflow\Contracts\AppliesOnWorkflowApproval;
use App\Domain\Workflow\Contracts\HasWorkflowSubjectEmployee;
use App\Enums\WorkflowApproverType;
use App\Enums\WorkflowInstanceStatus;
use App\Enums\WorkflowInstanceStepStatus;
use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use App\Models\WorkflowInstanceStep;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The one place workflow instances are started and advanced --
 * mirrors CLAUDE.md's "payroll logic never lives in controllers" rule
 * applied to a new domain: `Admin\WorkflowInstanceController` only
 * resolves the instance/step and checks `canAct()`, all state
 * transitions happen here.
 */
class WorkflowEngine
{
    public function start(WorkflowDefinition $definition, Model $subject, ?User $initiatedBy): WorkflowInstance
    {
        return DB::transaction(function () use ($definition, $subject, $initiatedBy) {
            $instance = WorkflowInstance::create([
                'workflow_definition_id' => $definition->id,
                'company_id' => $definition->company_id,
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'initiated_by' => $initiatedBy?->id,
                'status' => WorkflowInstanceStatus::InProgress,
            ]);

            foreach ($definition->steps as $step) {
                $instance->instanceSteps()->create([
                    'workflow_step_id' => $step->id,
                    'step_order' => $step->step_order,
                    'name' => $step->name,
                    'approver_type' => $step->approver_type,
                    'required_permission' => $step->required_permission,
                    'status' => WorkflowInstanceStepStatus::Pending,
                ]);
            }

            $firstStepOrder = $definition->steps->first()?->step_order;

            if ($firstStepOrder === null) {
                // No steps to gate on -- the same "nothing left to block
                // on" reasoning EmployeeOnboarding uses for a checklist
                // with zero tasks, not a stuck-forever instance.
                $this->finish($instance, WorkflowInstanceStatus::Approved);
                $this->applyApprovalIfApplicable($instance);

                return $instance->fresh();
            }

            $instance->update(['current_step_order' => $firstStepOrder]);
            $this->advancePastUnresolvableManagerSteps($instance);

            return $instance->fresh();
        });
    }

    public function act(WorkflowInstance $instance, WorkflowInstanceStep $step, User $actor, WorkflowInstanceStepStatus $decision, ?string $comments = null): void
    {
        abort_unless($instance->status === WorkflowInstanceStatus::InProgress, 422, 'This request is no longer in progress.');
        abort_unless($step->workflow_instance_id === $instance->id, 404);
        abort_unless($step->step_order === $instance->current_step_order, 422, 'This step is not currently awaiting action.');
        abort_unless($step->status === WorkflowInstanceStepStatus::Pending, 422, 'This step has already been decided.');
        abort_unless(in_array($decision, [WorkflowInstanceStepStatus::Approved, WorkflowInstanceStepStatus::Rejected], true), 422);
        abort_unless($this->canAct($step, $actor), 403);

        DB::transaction(function () use ($instance, $step, $actor, $decision, $comments) {
            $step->update([
                'status' => $decision,
                'acted_by' => $actor->id,
                'acted_at' => now(),
                'comments' => $comments,
            ]);

            if ($decision === WorkflowInstanceStepStatus::Rejected) {
                $this->finish($instance, WorkflowInstanceStatus::Rejected);
                $instance->instanceSteps()->where('status', WorkflowInstanceStepStatus::Pending)->update(['status' => WorkflowInstanceStepStatus::Skipped]);

                return;
            }

            $nextOrder = $instance->instanceSteps()->where('step_order', '>', $step->step_order)->min('step_order');

            if ($nextOrder === null) {
                $this->finish($instance, WorkflowInstanceStatus::Approved);
                $this->applyApprovalIfApplicable($instance);

                return;
            }

            $instance->update(['current_step_order' => $nextOrder]);
            $this->advancePastUnresolvableManagerSteps($instance->fresh());
        });
    }

    public function cancel(WorkflowInstance $instance, User $actor): void
    {
        abort_unless($instance->status === WorkflowInstanceStatus::InProgress, 422, 'This request is no longer in progress.');
        abort_unless($instance->initiated_by === $actor->id, 403);

        DB::transaction(function () use ($instance) {
            $instance->instanceSteps()->where('status', WorkflowInstanceStepStatus::Pending)->update(['status' => WorkflowInstanceStepStatus::Skipped]);
            $this->finish($instance, WorkflowInstanceStatus::Cancelled);
        });
    }

    public function canAct(WorkflowInstanceStep $step, User $user): bool
    {
        if ($step->status !== WorkflowInstanceStepStatus::Pending) {
            return false;
        }

        return match ($step->approver_type) {
            WorkflowApproverType::Manager => $this->resolveManagerUserId($step->workflowInstance) === $user->id,
            WorkflowApproverType::Permission => $step->required_permission !== null && $user->can($step->required_permission),
        };
    }

    /**
     * @return Collection<int, WorkflowInstanceStep>
     */
    public function pendingStepsFor(User $user): Collection
    {
        return WorkflowInstanceStep::query()
            ->where('status', WorkflowInstanceStepStatus::Pending)
            ->whereHas('workflowInstance', fn ($query) => $query->where('status', WorkflowInstanceStatus::InProgress))
            ->with(['workflowInstance.workflowDefinition', 'workflowInstance.subject'])
            ->get()
            ->filter(fn (WorkflowInstanceStep $step) => $step->step_order === $step->workflowInstance->current_step_order
                && $this->canAct($step, $user))
            ->values();
    }

    private function finish(WorkflowInstance $instance, WorkflowInstanceStatus $status): void
    {
        $instance->update(['status' => $status, 'current_step_order' => null, 'completed_at' => now()]);
    }

    private function applyApprovalIfApplicable(WorkflowInstance $instance): void
    {
        $subject = $instance->subject;

        if ($subject instanceof AppliesOnWorkflowApproval) {
            $subject->applyWorkflowApproval();
        }
    }

    /**
     * Skips every leading `Manager` step that has no resolvable
     * manager, in order, until the instance lands on a real actionable
     * step or runs out -- in which case it's approved outright (every
     * step was either resolvable-and-skippable or there were none).
     */
    private function advancePastUnresolvableManagerSteps(WorkflowInstance $instance): void
    {
        while ($instance->current_step_order !== null) {
            $step = $instance->instanceSteps()->where('step_order', $instance->current_step_order)->first();

            if ($step === null) {
                break;
            }

            if ($step->approver_type !== WorkflowApproverType::Manager || $this->resolveManagerUserId($instance) !== null) {
                break;
            }

            $step->update(['status' => WorkflowInstanceStepStatus::Skipped]);

            $nextOrder = $instance->instanceSteps()->where('step_order', '>', $step->step_order)->min('step_order');

            if ($nextOrder === null) {
                $this->finish($instance, WorkflowInstanceStatus::Approved);
                $this->applyApprovalIfApplicable($instance);

                return;
            }

            $instance->update(['current_step_order' => $nextOrder]);
        }
    }

    private function resolveManagerUserId(WorkflowInstance $instance): ?int
    {
        $subject = $instance->subject;

        if (! $subject instanceof HasWorkflowSubjectEmployee) {
            return null;
        }

        $manager = $subject->workflowEmployee()->currentEmployment?->manager;

        return $manager?->user?->id;
    }
}
