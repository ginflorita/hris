<?php

namespace Tests\Feature\Domain\Workflow;

use App\Domain\Workflow\Services\WorkflowEngine;
use App\Enums\WorkflowInstanceStatus;
use App\Enums\WorkflowInstanceStepStatus;
use App\Models\Employee;
use App\Models\EmployeeInformationChangeRequest;
use App\Models\Employment;
use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowStep;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Exercises App\Domain\Workflow\Services\WorkflowEngine directly, the
 * same "call the domain service via app()" convention LeaveTest/
 * PayrollAdjustmentTest already use for LeaveBalanceService/
 * PayrollCalculationService -- WorkflowInstanceController is a thin
 * wrapper around this engine (see its own docblock), so the real
 * business logic (step progression, manager/permission resolution,
 * rejection, snapshotting) belongs under test here rather than only
 * indirectly through HTTP.
 */
class WorkflowEngineTest extends TestCase
{
    use RefreshDatabase;

    private WorkflowEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->engine = app(WorkflowEngine::class);
    }

    private function subjectFor(Employee $employee, array $overrides = []): EmployeeInformationChangeRequest
    {
        return EmployeeInformationChangeRequest::factory()->forEmployee($employee)->create($overrides);
    }

    private function assertAborts(int $expectedStatus, callable $callback): void
    {
        try {
            $callback();
            $this->fail("Expected an abort with status {$expectedStatus}.");
        } catch (HttpException $e) {
            $this->assertSame($expectedStatus, $e->getStatusCode());
        }
    }

    public function test_starting_an_instance_with_no_steps_auto_approves_and_applies(): void
    {
        $employee = Employee::factory()->create(['mobile' => 'old-mobile']);
        $definition = WorkflowDefinition::factory()->create();
        $subject = $this->subjectFor($employee, ['requested_mobile' => 'new-mobile']);

        $instance = $this->engine->start($definition, $subject, User::factory()->create());

        $this->assertSame(WorkflowInstanceStatus::Approved, $instance->status);
        $this->assertNotNull($instance->completed_at);
        $this->assertNull($instance->current_step_order);
        $this->assertSame('new-mobile', $employee->fresh()->mobile);
    }

    public function test_starting_an_instance_snapshots_steps_from_the_definition_at_creation_time(): void
    {
        $employee = Employee::factory()->create();
        Employment::factory()->forEmployee($employee)->create();
        $definition = WorkflowDefinition::factory()->create();
        $step = WorkflowStep::factory()->for($definition, 'workflowDefinition')->create([
            'step_order' => 1, 'name' => 'HR Approval', 'approver_type' => 'permission', 'required_permission' => 'employees.update',
        ]);
        $subject = $this->subjectFor($employee);

        $instance = $this->engine->start($definition, $subject, null);

        $instanceStep = $instance->instanceSteps->sole();
        $this->assertSame('HR Approval', $instanceStep->name);
        $this->assertSame($step->id, $instanceStep->workflow_step_id);
        $this->assertSame('employees.update', $instanceStep->required_permission);

        $step->update(['name' => 'Renamed After The Fact', 'required_permission' => 'leave.approve']);

        $this->assertSame('HR Approval', $instanceStep->fresh()->name);
        $this->assertSame('employees.update', $instanceStep->fresh()->required_permission);
    }

    public function test_a_leading_manager_step_with_no_resolvable_manager_is_skipped(): void
    {
        $employee = Employee::factory()->create();
        Employment::factory()->forEmployee($employee)->create();
        $definition = WorkflowDefinition::factory()->create();
        WorkflowStep::factory()->manager()->for($definition, 'workflowDefinition')->create(['step_order' => 1]);
        WorkflowStep::factory()->for($definition, 'workflowDefinition')->create([
            'step_order' => 2, 'required_permission' => 'employees.update',
        ]);
        $subject = $this->subjectFor($employee);

        $instance = $this->engine->start($definition, $subject, null);

        $this->assertSame(WorkflowInstanceStatus::InProgress, $instance->status);
        $this->assertSame(2, $instance->current_step_order);
        $steps = $instance->instanceSteps;
        $this->assertSame(WorkflowInstanceStepStatus::Skipped, $steps->firstWhere('step_order', 1)->status);
        $this->assertSame(WorkflowInstanceStepStatus::Pending, $steps->firstWhere('step_order', 2)->status);
    }

    public function test_all_manager_steps_unresolvable_auto_approves_the_instance(): void
    {
        $employee = Employee::factory()->create(['mobile' => 'old']);
        Employment::factory()->forEmployee($employee)->create();
        $definition = WorkflowDefinition::factory()->create();
        WorkflowStep::factory()->manager()->for($definition, 'workflowDefinition')->create(['step_order' => 1]);
        $subject = $this->subjectFor($employee, ['requested_mobile' => 'new']);

        $instance = $this->engine->start($definition, $subject, null);

        $this->assertSame(WorkflowInstanceStatus::Approved, $instance->status);
        $this->assertSame(WorkflowInstanceStepStatus::Skipped, $instance->instanceSteps->sole()->status);
        $this->assertSame('new', $employee->fresh()->mobile);
    }

    public function test_a_manager_step_resolves_the_approver_via_the_subjects_current_employment(): void
    {
        $managerEmployee = Employee::factory()->create();
        $managerUser = User::factory()->create(['employee_id' => $managerEmployee->id]);

        $employee = Employee::factory()->for($managerEmployee->company, 'company')->create();
        Employment::factory()->forEmployee($employee)->create(['manager_id' => $managerEmployee->id]);

        $definition = WorkflowDefinition::factory()->create();
        WorkflowStep::factory()->manager()->for($definition, 'workflowDefinition')->create(['step_order' => 1]);
        $subject = $this->subjectFor($employee);

        $instance = $this->engine->start($definition, $subject, null);
        $step = $instance->instanceSteps->sole();

        $stranger = User::factory()->create();
        $this->assertFalse($this->engine->canAct($step, $stranger));
        $this->assertTrue($this->engine->canAct($step, $managerUser));

        $this->engine->act($instance, $step, $managerUser, WorkflowInstanceStepStatus::Approved);
        $this->assertSame(WorkflowInstanceStatus::Approved, $instance->fresh()->status);
    }

    public function test_a_multi_step_instance_advances_through_each_approver_and_applies_on_final_approval(): void
    {
        $employee = Employee::factory()->create(['mobile' => 'old-mobile', 'email' => 'old@example.test']);
        Employment::factory()->forEmployee($employee)->create();
        $definition = WorkflowDefinition::factory()->create();
        WorkflowStep::factory()->for($definition, 'workflowDefinition')->create([
            'step_order' => 1, 'name' => 'HR Review', 'required_permission' => 'employees.update',
        ]);
        WorkflowStep::factory()->for($definition, 'workflowDefinition')->create([
            'step_order' => 2, 'name' => 'Final Approval', 'required_permission' => 'leave.approve',
        ]);
        $subject = $this->subjectFor($employee, ['requested_mobile' => 'new-mobile', 'requested_email' => 'new@example.test']);

        $step1Actor = User::factory()->create();
        $step1Actor->givePermissionTo('employees.update');
        $step2Actor = User::factory()->create();
        $step2Actor->givePermissionTo('leave.approve');

        $instance = $this->engine->start($definition, $subject, null);
        $this->assertSame(1, $instance->current_step_order);

        $step1 = $instance->instanceSteps->firstWhere('step_order', 1);
        $this->engine->act($instance, $step1, $step1Actor, WorkflowInstanceStepStatus::Approved, 'Looks fine.');

        $instance->refresh();
        $this->assertSame(WorkflowInstanceStatus::InProgress, $instance->status);
        $this->assertSame(2, $instance->current_step_order);
        $this->assertSame('old-mobile', $employee->fresh()->mobile);

        $step2 = $instance->instanceSteps()->where('step_order', 2)->first();
        $this->engine->act($instance, $step2, $step2Actor, WorkflowInstanceStepStatus::Approved);

        $instance->refresh();
        $this->assertSame(WorkflowInstanceStatus::Approved, $instance->status);
        $this->assertNotNull($instance->completed_at);
        $employee->refresh();
        $this->assertSame('new-mobile', $employee->mobile);
        $this->assertSame('new@example.test', $employee->email);
    }

    public function test_rejecting_a_step_terminates_the_instance_and_skips_remaining_pending_steps(): void
    {
        $definition = WorkflowDefinition::factory()->create();
        WorkflowStep::factory()->for($definition, 'workflowDefinition')->create([
            'step_order' => 1, 'required_permission' => 'employees.update',
        ]);
        WorkflowStep::factory()->for($definition, 'workflowDefinition')->create([
            'step_order' => 2, 'required_permission' => 'leave.approve',
        ]);
        $employee = Employee::factory()->create(['mobile' => 'original']);
        Employment::factory()->forEmployee($employee)->create();
        $subject = $this->subjectFor($employee, ['requested_mobile' => 'changed']);
        $actor = User::factory()->create();
        $actor->givePermissionTo('employees.update');

        $instance = $this->engine->start($definition, $subject, null);
        $step1 = $instance->instanceSteps->firstWhere('step_order', 1);

        $this->engine->act($instance, $step1, $actor, WorkflowInstanceStepStatus::Rejected, 'Not valid.');

        $instance->refresh();
        $this->assertSame(WorkflowInstanceStatus::Rejected, $instance->status);
        $this->assertNotNull($instance->completed_at);
        $this->assertNull($instance->current_step_order);

        $step1->refresh();
        $this->assertSame(WorkflowInstanceStepStatus::Rejected, $step1->status);
        $this->assertSame('Not valid.', $step1->comments);
        $this->assertSame($actor->id, $step1->acted_by);

        $step2 = $instance->instanceSteps()->where('step_order', 2)->first();
        $this->assertSame(WorkflowInstanceStepStatus::Skipped, $step2->status);

        $this->assertSame('original', $employee->fresh()->mobile);
    }

    public function test_pending_steps_for_only_returns_the_current_actionable_step(): void
    {
        $definition = WorkflowDefinition::factory()->create();
        WorkflowStep::factory()->for($definition, 'workflowDefinition')->create([
            'step_order' => 1, 'required_permission' => 'employees.update',
        ]);
        WorkflowStep::factory()->for($definition, 'workflowDefinition')->create([
            'step_order' => 2, 'required_permission' => 'leave.approve',
        ]);
        $employee = Employee::factory()->create();
        Employment::factory()->forEmployee($employee)->create();
        $subject = $this->subjectFor($employee);

        $step1Holder = User::factory()->create();
        $step1Holder->givePermissionTo('employees.update');
        $step2Holder = User::factory()->create();
        $step2Holder->givePermissionTo('leave.approve');

        $instance = $this->engine->start($definition, $subject, null);

        $this->assertCount(0, $this->engine->pendingStepsFor($step2Holder));
        $this->assertCount(1, $this->engine->pendingStepsFor($step1Holder));

        $step1 = $instance->instanceSteps->firstWhere('step_order', 1);
        $this->engine->act($instance, $step1, $step1Holder, WorkflowInstanceStepStatus::Approved);

        $this->assertCount(0, $this->engine->pendingStepsFor($step1Holder));
        $this->assertCount(1, $this->engine->pendingStepsFor($step2Holder));
    }

    public function test_can_act_returns_false_for_the_wrong_user_and_once_a_decision_is_made(): void
    {
        $definition = WorkflowDefinition::factory()->create();
        WorkflowStep::factory()->for($definition, 'workflowDefinition')->create([
            'step_order' => 1, 'required_permission' => 'employees.update',
        ]);
        $subject = $this->subjectFor(Employee::factory()->create());
        $holder = User::factory()->create();
        $holder->givePermissionTo('employees.update');
        $stranger = User::factory()->create();

        $instance = $this->engine->start($definition, $subject, null);
        $step = $instance->instanceSteps->sole();

        $this->assertFalse($this->engine->canAct($step, $stranger));
        $this->assertTrue($this->engine->canAct($step, $holder));

        $this->engine->act($instance, $step, $holder, WorkflowInstanceStepStatus::Approved);

        $this->assertFalse($this->engine->canAct($step->fresh(), $holder));
    }

    public function test_act_rejects_a_decision_on_a_non_current_step(): void
    {
        $definition = WorkflowDefinition::factory()->create();
        WorkflowStep::factory()->for($definition, 'workflowDefinition')->create([
            'step_order' => 1, 'required_permission' => 'employees.update',
        ]);
        WorkflowStep::factory()->for($definition, 'workflowDefinition')->create([
            'step_order' => 2, 'required_permission' => 'leave.approve',
        ]);
        $subject = $this->subjectFor(Employee::factory()->create());
        $actor = User::factory()->create();
        $actor->givePermissionTo('leave.approve');

        $instance = $this->engine->start($definition, $subject, null);
        $step2 = $instance->instanceSteps->firstWhere('step_order', 2);

        $this->assertAborts(422, fn () => $this->engine->act($instance, $step2, $actor, WorkflowInstanceStepStatus::Approved));
    }

    public function test_act_rejects_a_step_that_does_not_belong_to_the_instance(): void
    {
        $definitionA = WorkflowDefinition::factory()->create();
        WorkflowStep::factory()->for($definitionA, 'workflowDefinition')->create([
            'step_order' => 1, 'required_permission' => 'employees.update',
        ]);
        $definitionB = WorkflowDefinition::factory()->create();
        WorkflowStep::factory()->for($definitionB, 'workflowDefinition')->create([
            'step_order' => 1, 'required_permission' => 'employees.update',
        ]);

        $instanceA = $this->engine->start($definitionA, $this->subjectFor(Employee::factory()->create()), null);
        $instanceB = $this->engine->start($definitionB, $this->subjectFor(Employee::factory()->create()), null);
        $stepFromB = $instanceB->instanceSteps->sole();

        $actor = User::factory()->create();
        $actor->givePermissionTo('employees.update');

        $this->assertAborts(404, fn () => $this->engine->act($instanceA, $stepFromB, $actor, WorkflowInstanceStepStatus::Approved));
    }

    public function test_act_rejects_a_step_that_is_no_longer_pending(): void
    {
        $definition = WorkflowDefinition::factory()->create();
        WorkflowStep::factory()->for($definition, 'workflowDefinition')->create([
            'step_order' => 1, 'required_permission' => 'employees.update',
        ]);
        $actor = User::factory()->create();
        $actor->givePermissionTo('employees.update');
        $instance = $this->engine->start($definition, $this->subjectFor(Employee::factory()->create()), null);
        $step = $instance->instanceSteps->sole();

        // Simulate the step already having been decided (e.g. a
        // concurrent request) while the instance's current_step_order
        // still points at it.
        $step->update(['status' => WorkflowInstanceStepStatus::Approved, 'acted_by' => $actor->id, 'acted_at' => now()]);

        $this->assertAborts(422, fn () => $this->engine->act($instance->fresh(), $step->fresh(), $actor, WorkflowInstanceStepStatus::Approved));
    }

    public function test_act_rejects_when_the_instance_is_no_longer_in_progress(): void
    {
        $definition = WorkflowDefinition::factory()->create();
        $step = WorkflowStep::factory()->for($definition, 'workflowDefinition')->create([
            'step_order' => 1, 'required_permission' => 'employees.update',
        ]);
        $actor = User::factory()->create();
        $actor->givePermissionTo('employees.update');
        $instance = $this->engine->start($definition, $this->subjectFor(Employee::factory()->create()), $actor);
        $instanceStep = $instance->instanceSteps->sole();

        $this->engine->cancel($instance, $actor);

        $this->assertAborts(422, fn () => $this->engine->act($instance->fresh(), $instanceStep->fresh(), $actor, WorkflowInstanceStepStatus::Approved));
    }

    public function test_cancel_requires_the_initiator_and_skips_remaining_pending_steps(): void
    {
        $definition = WorkflowDefinition::factory()->create();
        WorkflowStep::factory()->for($definition, 'workflowDefinition')->create([
            'step_order' => 1, 'required_permission' => 'employees.update',
        ]);
        $initiator = User::factory()->create();
        $subject = $this->subjectFor(Employee::factory()->create());

        $instance = $this->engine->start($definition, $subject, $initiator);

        $stranger = User::factory()->create();
        $this->assertAborts(403, fn () => $this->engine->cancel($instance, $stranger));

        $this->engine->cancel($instance, $initiator);

        $instance->refresh();
        $this->assertSame(WorkflowInstanceStatus::Cancelled, $instance->status);
        $this->assertSame(WorkflowInstanceStepStatus::Skipped, $instance->instanceSteps->sole()->status);
    }

    public function test_cancel_rejects_an_instance_that_is_no_longer_in_progress(): void
    {
        $definition = WorkflowDefinition::factory()->create();
        $initiator = User::factory()->create();
        $subject = $this->subjectFor(Employee::factory()->create());

        $instance = $this->engine->start($definition, $subject, $initiator);
        $this->assertSame(WorkflowInstanceStatus::Approved, $instance->status);

        $this->assertAborts(422, fn () => $this->engine->cancel($instance, $initiator));
    }
}
