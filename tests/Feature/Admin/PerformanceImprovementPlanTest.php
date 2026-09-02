<?php

namespace Tests\Feature\Admin;

use App\Enums\PerformanceImprovementPlanStatus;
use App\Models\Employee;
use App\Models\PerformanceImprovementPlan;
use App\Models\PerformanceReview;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceImprovementPlanTest extends TestCase
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
        $user->givePermissionTo(['performance.view', 'performance.manage']);

        return $user;
    }

    public function test_adding_a_plan(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $initiator = Employee::factory()->create(['company_id' => $employee->company_id]);

        $this->actingAs($user)->post(route('admin.employees.performance-improvement-plans.store', $employee), [
            'initiated_by' => $initiator->id,
            'reason' => 'Missed three consecutive deadlines.',
            'goals' => 'Deliver assigned tasks on time for the next quarter.',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
        ])->assertRedirect();

        $plan = PerformanceImprovementPlan::sole();
        $this->assertSame($employee->id, $plan->employee_id);
        $this->assertSame(PerformanceImprovementPlanStatus::Active, $plan->status);
        $this->assertNull($plan->performance_review_id);
    }

    public function test_can_link_a_triggering_review_owned_by_the_same_employee(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $initiator = Employee::factory()->create(['company_id' => $employee->company_id]);
        $review = PerformanceReview::factory()->forEmployee($employee)->create();
        $otherEmployeesReview = PerformanceReview::factory()->create();

        $this->actingAs($user)->post(route('admin.employees.performance-improvement-plans.store', $employee), [
            'performance_review_id' => $otherEmployeesReview->id,
            'initiated_by' => $initiator->id,
            'reason' => 'x',
            'goals' => 'y',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
        ])->assertSessionHasErrors('performance_review_id');

        $this->actingAs($user)->post(route('admin.employees.performance-improvement-plans.store', $employee), [
            'performance_review_id' => $review->id,
            'initiated_by' => $initiator->id,
            'reason' => 'x',
            'goals' => 'y',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
        ])->assertRedirect();

        $this->assertSame($review->id, PerformanceImprovementPlan::sole()->performance_review_id);
    }

    public function test_initiator_must_belong_to_the_employees_company(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $wrongCompanyInitiator = Employee::factory()->create();

        $this->actingAs($user)->post(route('admin.employees.performance-improvement-plans.store', $employee), [
            'initiated_by' => $wrongCompanyInitiator->id,
            'reason' => 'x',
            'goals' => 'y',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
        ])->assertSessionHasErrors('initiated_by');
    }

    public function test_closing_a_plan_requires_a_valid_outcome_and_locks_it(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $plan = PerformanceImprovementPlan::factory()->forEmployee($employee)->create();

        $this->actingAs($user)->put(route('admin.employees.performance-improvement-plans.close', [$employee, $plan]), [
            'status' => 'active',
        ])->assertSessionHasErrors('status');

        $this->actingAs($user)->put(route('admin.employees.performance-improvement-plans.close', [$employee, $plan]), [
            'status' => 'successful',
            'outcome_notes' => 'Employee met all improvement goals.',
        ])->assertRedirect();

        $plan->refresh();
        $this->assertSame(PerformanceImprovementPlanStatus::Successful, $plan->status);
        $this->assertNotNull($plan->closed_at);
        $this->assertSame($user->id, $plan->closed_by);

        // A closed plan can no longer be edited, closed again, or removed.
        $this->actingAs($user)->put(route('admin.employees.performance-improvement-plans.update', [$employee, $plan]), [
            'initiated_by' => $plan->initiated_by,
            'reason' => 'x',
            'goals' => 'y',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
        ])->assertStatus(422);

        $this->actingAs($user)->put(route('admin.employees.performance-improvement-plans.close', [$employee, $plan]), [
            'status' => 'unsuccessful',
        ])->assertStatus(422);

        $this->actingAs($user)->delete(route('admin.employees.performance-improvement-plans.destroy', [$employee, $plan]))
            ->assertStatus(422);
    }

    public function test_a_plan_from_another_employee_cannot_be_acted_on_through_this_one(): void
    {
        $user = $this->manager();
        $employeeA = Employee::factory()->create();
        $employeeB = Employee::factory()->create();
        $plan = PerformanceImprovementPlan::factory()->forEmployee($employeeB)->create();

        $this->actingAs($user)->put(route('admin.employees.performance-improvement-plans.close', [$employeeA, $plan]), [
            'status' => 'successful',
        ])->assertNotFound();
    }

    public function test_removing_an_active_plan(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $plan = PerformanceImprovementPlan::factory()->forEmployee($employee)->create();

        $this->actingAs($user)->delete(route('admin.employees.performance-improvement-plans.destroy', [$employee, $plan]))
            ->assertRedirect();

        $this->assertSame(0, PerformanceImprovementPlan::count());
    }

    public function test_requires_permission(): void
    {
        $plain = User::factory()->create();
        $employee = Employee::factory()->create();

        $this->actingAs($plain)->post(route('admin.employees.performance-improvement-plans.store', $employee), [])->assertForbidden();
    }
}
