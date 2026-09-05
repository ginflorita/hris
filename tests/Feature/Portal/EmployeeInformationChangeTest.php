<?php

namespace Tests\Feature\Portal;

use App\Enums\WorkflowApproverType;
use App\Enums\WorkflowInstanceStatus;
use App\Models\Employee;
use App\Models\EmployeeInformationChangeRequest;
use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeInformationChangeTest extends TestCase
{
    use RefreshDatabase;

    private function employeeUser(): User
    {
        $employee = Employee::factory()->create(['mobile' => 'old-mobile']);

        return User::factory()->create(['employee_id' => $employee->id]);
    }

    private function activeDefinitionFor(Employee $employee): WorkflowDefinition
    {
        $definition = WorkflowDefinition::factory()->create(['company_id' => $employee->company_id]);
        WorkflowStep::factory()->for($definition, 'workflowDefinition')->create([
            'step_order' => 1, 'approver_type' => WorkflowApproverType::Permission, 'required_permission' => 'employees.update',
        ]);

        return $definition;
    }

    public function test_unlinked_account_sees_a_friendly_message(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('portal.information-change.index'))
            ->assertOk()
            ->assertSee("isn't linked to an employee record", false);
    }

    public function test_index_hides_the_request_button_without_an_active_definition(): void
    {
        $user = $this->employeeUser();

        $this->actingAs($user)->get(route('portal.information-change.index'))
            ->assertOk()
            ->assertSee('No approval workflow is configured', false)
            ->assertDontSee('Request a Change');
    }

    public function test_store_starts_a_workflow_instance_against_the_active_definition(): void
    {
        $user = $this->employeeUser();
        $this->activeDefinitionFor($user->employee);

        $this->actingAs($user)->post(route('portal.information-change.store'), [
            'requested_mobile' => 'new-mobile',
            'reason' => 'Changed number.',
        ])->assertRedirect()->assertSessionHas('status');

        $changeRequest = EmployeeInformationChangeRequest::sole();
        $this->assertSame($user->employee_id, $changeRequest->employee_id);
        $this->assertSame('new-mobile', $changeRequest->requested_mobile);
        $this->assertSame(WorkflowInstanceStatus::InProgress, $changeRequest->workflowInstance->status);

        // Nothing on the Employee record itself changes until approved.
        $this->assertSame('old-mobile', $user->employee->fresh()->mobile);
    }

    public function test_store_requires_at_least_one_field_to_change(): void
    {
        $user = $this->employeeUser();
        $this->activeDefinitionFor($user->employee);

        $this->actingAs($user)->post(route('portal.information-change.store'), [
            'reason' => 'No actual change.',
        ])->assertSessionHasErrors();

        $this->assertSame(0, EmployeeInformationChangeRequest::count());
    }

    public function test_store_is_blocked_without_an_active_definition(): void
    {
        $user = $this->employeeUser();

        $this->actingAs($user)->post(route('portal.information-change.store'), [
            'requested_mobile' => 'new-mobile',
            'reason' => 'Changed number.',
        ])->assertStatus(422);

        $this->assertSame(0, EmployeeInformationChangeRequest::count());
    }

    public function test_history_lists_prior_requests_with_status(): void
    {
        $user = $this->employeeUser();
        $this->activeDefinitionFor($user->employee);

        $this->actingAs($user)->post(route('portal.information-change.store'), [
            'requested_email' => 'new@example.test',
            'reason' => 'Personal email retired.',
        ]);

        $this->actingAs($user)->get(route('portal.information-change.index'))
            ->assertOk()
            ->assertSee('Personal email retired.')
            ->assertSee('In Progress');
    }
}
