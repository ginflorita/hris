<?php

namespace Tests\Feature\Admin\Workflow;

use App\Domain\Workflow\Services\WorkflowEngine;
use App\Enums\WorkflowInstanceStatus;
use App\Models\Employee;
use App\Models\EmployeeInformationChangeRequest;
use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The HTTP layer around WorkflowEngine (see WorkflowEngineTest for the
 * engine mechanics themselves). No permission-based route middleware or
 * $this->authorize() gates this controller at all -- "who can act" is
 * resolved dynamically per step, so every assertion here is about that
 * dynamic gate (index's filtered inbox, show's canAct||hasActed check),
 * not a static permission check.
 */
class WorkflowInstanceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    /**
     * @return array{0: WorkflowInstance, 1: User}
     */
    private function startSingleStepInstance(string $permission = 'employees.update'): array
    {
        $definition = WorkflowDefinition::factory()->create();
        WorkflowStep::factory()->for($definition, 'workflowDefinition')->create([
            'step_order' => 1, 'required_permission' => $permission,
        ]);
        $subject = EmployeeInformationChangeRequest::factory()
            ->forEmployee(Employee::factory()->create())
            ->create();
        $initiator = User::factory()->create();

        $instance = app(WorkflowEngine::class)->start($definition, $subject, $initiator);

        return [$instance, $initiator];
    }

    public function test_index_lists_only_steps_the_viewer_can_currently_act_on(): void
    {
        [$instance] = $this->startSingleStepInstance('employees.update');

        $holder = User::factory()->create();
        $holder->givePermissionTo('employees.update');
        $stranger = User::factory()->create();
        $stranger->givePermissionTo('leave.approve');

        $this->actingAs($stranger)->get(route('admin.workflow.instances.index'))
            ->assertOk()
            ->assertViewHas('pendingSteps', fn ($steps) => $steps->isEmpty());

        $this->actingAs($holder)->get(route('admin.workflow.instances.index'))
            ->assertOk()
            ->assertViewHas('pendingSteps', fn ($steps) => $steps->count() === 1
                && $steps->first()->workflowInstance->is($instance));
    }

    public function test_show_is_forbidden_for_a_user_who_cannot_act_and_never_acted(): void
    {
        [$instance] = $this->startSingleStepInstance('employees.update');
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get(route('admin.workflow.instances.show', $instance))->assertForbidden();
    }

    public function test_show_is_visible_with_action_buttons_to_a_user_who_can_currently_act(): void
    {
        [$instance] = $this->startSingleStepInstance('employees.update');
        $holder = User::factory()->create();
        $holder->givePermissionTo('employees.update');

        $this->actingAs($holder)->get(route('admin.workflow.instances.show', $instance))
            ->assertOk()
            ->assertViewHas('canAct', true)
            ->assertSee('Your decision');
    }

    public function test_show_remains_visible_after_acting_but_without_action_buttons(): void
    {
        [$instance] = $this->startSingleStepInstance('employees.update');
        $holder = User::factory()->create();
        $holder->givePermissionTo('employees.update');

        $this->actingAs($holder)->put(route('admin.workflow.instances.approve', $instance))->assertRedirect();

        $this->actingAs($holder)->get(route('admin.workflow.instances.show', $instance))
            ->assertOk()
            ->assertViewHas('canAct', false)
            ->assertDontSee('Your decision');
    }

    public function test_approve_advances_a_multi_step_instance_and_records_the_decision(): void
    {
        $definition = WorkflowDefinition::factory()->create();
        WorkflowStep::factory()->for($definition, 'workflowDefinition')->create([
            'step_order' => 1, 'required_permission' => 'employees.update',
        ]);
        WorkflowStep::factory()->for($definition, 'workflowDefinition')->create([
            'step_order' => 2, 'required_permission' => 'leave.approve',
        ]);
        $subject = EmployeeInformationChangeRequest::factory()->forEmployee(Employee::factory()->create())->create();
        $instance = app(WorkflowEngine::class)->start($definition, $subject, null);

        $holder = User::factory()->create();
        $holder->givePermissionTo('employees.update');

        $this->actingAs($holder)->put(route('admin.workflow.instances.approve', $instance))
            ->assertRedirect(route('admin.workflow.instances.index'))
            ->assertSessionHas('status');

        $instance->refresh();
        $this->assertSame(2, $instance->current_step_order);
        $decidedStep = $instance->instanceSteps()->where('step_order', 1)->first();
        $this->assertSame($holder->id, $decidedStep->acted_by);
    }

    public function test_approve_is_forbidden_for_a_user_who_cannot_act(): void
    {
        [$instance] = $this->startSingleStepInstance('employees.update');
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->put(route('admin.workflow.instances.approve', $instance))->assertForbidden();
    }

    public function test_reject_requires_comments_and_terminates_the_instance(): void
    {
        [$instance] = $this->startSingleStepInstance('employees.update');
        $holder = User::factory()->create();
        $holder->givePermissionTo('employees.update');

        $this->actingAs($holder)->put(route('admin.workflow.instances.reject', $instance), [])
            ->assertSessionHasErrors('comments');

        $this->actingAs($holder)->put(route('admin.workflow.instances.reject', $instance), ['comments' => 'Missing info.'])
            ->assertRedirect(route('admin.workflow.instances.index'));

        $instance->refresh();
        $this->assertSame(WorkflowInstanceStatus::Rejected, $instance->status);
        $this->assertSame('Missing info.', $instance->instanceSteps->sole()->comments);
    }

    public function test_approve_returns_a_client_error_when_no_step_is_awaiting_action(): void
    {
        [$instance] = $this->startSingleStepInstance('employees.update');
        $holder = User::factory()->create();
        $holder->givePermissionTo('employees.update');

        $this->actingAs($holder)->put(route('admin.workflow.instances.approve', $instance))->assertRedirect();

        $this->actingAs($holder)->put(route('admin.workflow.instances.approve', $instance))->assertStatus(422);
    }
}
