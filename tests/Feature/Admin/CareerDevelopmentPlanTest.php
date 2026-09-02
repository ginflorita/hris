<?php

namespace Tests\Feature\Admin;

use App\Enums\CareerDevelopmentPlanStatus;
use App\Models\CareerDevelopmentPlan;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CareerDevelopmentPlanTest extends TestCase
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
        $position = Position::factory()->create(['company_id' => $employee->company_id]);

        $this->actingAs($user)->post(route('admin.employees.career-development-plans.store', $employee), [
            'target_position_id' => $position->id,
            'target_date' => '2028-01-01',
            'development_actions' => 'Complete leadership training and shadow the current lead for two quarters.',
        ])->assertRedirect();

        $plan = CareerDevelopmentPlan::sole();
        $this->assertSame($employee->id, $plan->employee_id);
        $this->assertSame(CareerDevelopmentPlanStatus::Active, $plan->status);
    }

    public function test_target_position_must_belong_to_the_employees_company(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $wrongPosition = Position::factory()->create();

        $this->actingAs($user)->post(route('admin.employees.career-development-plans.store', $employee), [
            'target_position_id' => $wrongPosition->id,
            'development_actions' => 'x',
        ])->assertSessionHasErrors('target_position_id');
    }

    public function test_achieve_and_cancel_are_only_reachable_from_active(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $plan = CareerDevelopmentPlan::factory()->forEmployee($employee)->create();

        $this->actingAs($user)->put(route('admin.employees.career-development-plans.achieve', [$employee, $plan]))
            ->assertRedirect();
        $this->assertSame(CareerDevelopmentPlanStatus::Achieved, $plan->refresh()->status);

        $this->actingAs($user)->put(route('admin.employees.career-development-plans.cancel', [$employee, $plan]))
            ->assertStatus(422);
    }

    public function test_an_achieved_plan_cannot_be_edited_or_removed(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $plan = CareerDevelopmentPlan::factory()->forEmployee($employee)->create(['status' => CareerDevelopmentPlanStatus::Achieved]);

        $this->actingAs($user)->put(route('admin.employees.career-development-plans.update', [$employee, $plan]), [
            'development_actions' => 'y',
        ])->assertStatus(422);

        $this->actingAs($user)->delete(route('admin.employees.career-development-plans.destroy', [$employee, $plan]))
            ->assertStatus(422);
    }

    public function test_a_plan_from_another_employee_cannot_be_acted_on_through_this_one(): void
    {
        $user = $this->manager();
        $employeeA = Employee::factory()->create();
        $employeeB = Employee::factory()->create();
        $plan = CareerDevelopmentPlan::factory()->forEmployee($employeeB)->create();

        $this->actingAs($user)->put(route('admin.employees.career-development-plans.achieve', [$employeeA, $plan]))
            ->assertNotFound();
    }

    public function test_requires_permission(): void
    {
        $plain = User::factory()->create();
        $employee = Employee::factory()->create();

        $this->actingAs($plain)->post(route('admin.employees.career-development-plans.store', $employee), [])->assertForbidden();
    }
}
