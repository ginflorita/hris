<?php

namespace Tests\Feature\Admin\Workflow;

use App\Domain\Workflow\Services\WorkflowEngine;
use App\Enums\WorkflowProcessType;
use App\Models\Company;
use App\Models\EmployeeInformationChangeRequest;
use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowStep;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowDefinitionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function manager(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['workflow.view', 'workflow.manage']);

        return $user;
    }

    private function viewer(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('workflow.view');

        return $user;
    }

    public function test_index_and_create_require_the_right_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.workflow.definitions.index'))->assertForbidden();
        $this->actingAs($this->viewer())->get(route('admin.workflow.definitions.index'))->assertOk();
        $this->actingAs($this->viewer())->get(route('admin.workflow.definitions.create'))->assertForbidden();
        $this->actingAs($this->manager())->get(route('admin.workflow.definitions.create'))->assertOk();
    }

    public function test_workflow_definition_crud(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();

        $this->actingAs($user)->post(route('admin.workflow.definitions.store'), [
            'company_id' => $company->id,
            'name' => 'Employee Info Change',
            'process_type' => WorkflowProcessType::EmployeeInformationChange->value,
            'description' => 'Approve changes to personal information.',
        ])->assertRedirect();

        $definition = WorkflowDefinition::sole();
        $this->assertTrue($definition->is_active);
        $this->assertSame(WorkflowProcessType::EmployeeInformationChange, $definition->process_type);

        $this->actingAs($user)->put(route('admin.workflow.definitions.update', $definition), [
            'company_id' => $company->id,
            'name' => 'Employee Info Change',
            'process_type' => WorkflowProcessType::EmployeeInformationChange->value,
            // is_active omitted -> unchecked
        ])->assertRedirect(route('admin.workflow.definitions.index'));

        $definition->refresh();
        $this->assertFalse($definition->is_active);

        $this->actingAs($user)->delete(route('admin.workflow.definitions.destroy', $definition))
            ->assertRedirect(route('admin.workflow.definitions.index'));
        $this->assertSoftDeleted($definition);
    }

    public function test_workflow_step_crud_and_ordering(): void
    {
        $user = $this->manager();
        $definition = WorkflowDefinition::factory()->create();

        $this->actingAs($user)->post(route('admin.workflow.definitions.steps.store', $definition), [
            'step_order' => 1,
            'name' => 'Manager Acknowledgment',
            'approver_type' => 'manager',
        ])->assertRedirect();

        $step = WorkflowStep::sole();
        $this->assertSame('manager', $step->approver_type->value);
        $this->assertNull($step->required_permission);

        $this->actingAs($user)->put(route('admin.workflow.definitions.steps.update', [$definition, $step]), [
            'step_order' => 1,
            'name' => 'HR Approval',
            'approver_type' => 'permission',
            'required_permission' => 'employees.update',
        ])->assertRedirect();

        $step->refresh();
        $this->assertSame('permission', $step->approver_type->value);
        $this->assertSame('employees.update', $step->required_permission);

        $this->actingAs($user)->delete(route('admin.workflow.definitions.steps.destroy', [$definition, $step]))
            ->assertRedirect();
        $this->assertModelMissing($step);
    }

    public function test_a_permission_approver_step_requires_a_valid_permission_name(): void
    {
        $user = $this->manager();
        $definition = WorkflowDefinition::factory()->create();

        $this->actingAs($user)->post(route('admin.workflow.definitions.steps.store', $definition), [
            'step_order' => 1,
            'name' => 'HR Approval',
            'approver_type' => 'permission',
            // required_permission omitted
        ])->assertSessionHasErrors('required_permission');

        $this->actingAs($user)->post(route('admin.workflow.definitions.steps.store', $definition), [
            'step_order' => 1,
            'name' => 'HR Approval',
            'approver_type' => 'permission',
            'required_permission' => 'not-a-real-permission',
        ])->assertSessionHasErrors('required_permission');

        $this->assertSame(0, WorkflowStep::count());
    }

    public function test_step_order_must_be_unique_within_a_definition(): void
    {
        $user = $this->manager();
        $definition = WorkflowDefinition::factory()->create();
        WorkflowStep::factory()->for($definition, 'workflowDefinition')->create(['step_order' => 1]);

        $this->actingAs($user)->post(route('admin.workflow.definitions.steps.store', $definition), [
            'step_order' => 1,
            'name' => 'Duplicate order',
            'approver_type' => 'manager',
        ])->assertSessionHasErrors('step_order');
    }

    public function test_a_step_from_another_definition_returns_404(): void
    {
        $user = $this->manager();
        $definitionA = WorkflowDefinition::factory()->create();
        $definitionB = WorkflowDefinition::factory()->create();
        $step = WorkflowStep::factory()->for($definitionA, 'workflowDefinition')->create();

        $this->actingAs($user)->put(route('admin.workflow.definitions.steps.update', [$definitionB, $step]), [
            'step_order' => 1,
            'name' => 'Renamed',
            'approver_type' => 'manager',
        ])->assertNotFound();
    }

    public function test_a_definition_with_instance_history_cannot_be_deleted(): void
    {
        $user = $this->manager();
        $definition = WorkflowDefinition::factory()->create();
        $subject = EmployeeInformationChangeRequest::factory()->create();
        app(WorkflowEngine::class)->start($definition, $subject, null);

        $this->actingAs($user)->delete(route('admin.workflow.definitions.destroy', $definition))
            ->assertRedirect()
            ->assertSessionHasErrors('workflowDefinition');

        $this->assertNotSoftDeleted($definition);
    }
}
