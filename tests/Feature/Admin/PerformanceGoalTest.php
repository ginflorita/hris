<?php

namespace Tests\Feature\Admin;

use App\Enums\PerformanceGoalStatus;
use App\Models\Employee;
use App\Models\PerformanceCycle;
use App\Models\PerformanceGoal;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceGoalTest extends TestCase
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

    public function test_adding_a_goal_with_a_measurable_target(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $cycle = PerformanceCycle::factory()->create(['company_id' => $employee->company_id]);

        $this->actingAs($user)->post(route('admin.employees.performance-goals.store', $employee), [
            'performance_cycle_id' => $cycle->id,
            'title' => 'Increase sales',
            'target_value' => 100000,
            'actual_value' => 25000,
            'unit' => 'USD',
            'weight' => 40,
        ])->assertRedirect();

        $goal = PerformanceGoal::sole();
        $this->assertSame($employee->id, $goal->employee_id);
        $this->assertSame(PerformanceGoalStatus::NotStarted, $goal->status);
        $this->assertSame('100000.00', (string) $goal->target_value);
    }

    public function test_cycle_must_belong_to_the_employees_company(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $wrongCycle = PerformanceCycle::factory()->create();

        $this->actingAs($user)->post(route('admin.employees.performance-goals.store', $employee), [
            'performance_cycle_id' => $wrongCycle->id,
            'title' => 'Increase sales',
        ])->assertSessionHasErrors('performance_cycle_id');
    }

    public function test_updating_status(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $cycle = PerformanceCycle::factory()->create(['company_id' => $employee->company_id]);
        $goal = PerformanceGoal::factory()->forEmployee($employee)->create(['performance_cycle_id' => $cycle->id]);

        $this->actingAs($user)->put(route('admin.employees.performance-goals.update', [$employee, $goal]), [
            'performance_cycle_id' => $cycle->id,
            'title' => $goal->title,
            'status' => 'completed',
        ])->assertRedirect()->assertSessionDoesntHaveErrors();

        $this->assertSame(PerformanceGoalStatus::Completed, $goal->refresh()->status);
    }

    public function test_a_goal_from_another_employee_cannot_be_updated_through_this_one(): void
    {
        $user = $this->manager();
        $employeeA = Employee::factory()->create();
        $employeeB = Employee::factory()->create();
        $goal = PerformanceGoal::factory()->forEmployee($employeeB)->create();

        $this->actingAs($user)->put(route('admin.employees.performance-goals.update', [$employeeA, $goal]), [
            'performance_cycle_id' => $goal->performance_cycle_id,
            'title' => 'x',
            'status' => 'completed',
        ])->assertNotFound();
    }

    public function test_removing_a_goal(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $goal = PerformanceGoal::factory()->forEmployee($employee)->create();

        $this->actingAs($user)->delete(route('admin.employees.performance-goals.destroy', [$employee, $goal]))->assertRedirect();

        $this->assertSame(0, PerformanceGoal::count());
    }

    public function test_requires_permission(): void
    {
        $plain = User::factory()->create();
        $employee = Employee::factory()->create();

        $this->actingAs($plain)->post(route('admin.employees.performance-goals.store', $employee), [])->assertForbidden();
    }
}
