<?php

namespace Tests\Feature\Admin;

use App\Models\BenefitEnrollment;
use App\Models\BenefitPlan;
use App\Models\Employee;
use App\Models\EmployeeDependent;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BenefitEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function officer(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['benefits.view', 'benefits.manage']);

        return $user;
    }

    public function test_enrolling_in_a_plan(): void
    {
        $user = $this->officer();
        $employee = Employee::factory()->create();
        $plan = BenefitPlan::factory()->create(['company_id' => $employee->company_id]);

        $this->actingAs($user)->post(route('admin.employees.benefit-enrollments.store', $employee), [
            'benefit_plan_id' => $plan->id,
            'employee_contribution' => 250,
            'employer_contribution' => 750,
            'effective_date' => '2026-01-01',
        ])->assertRedirect();

        $enrollment = BenefitEnrollment::sole();
        $this->assertSame($employee->id, $enrollment->employee_id);
        $this->assertNull($enrollment->end_date);
        $this->assertSame('250.00', (string) $enrollment->employee_contribution);
    }

    public function test_plan_must_belong_to_the_employees_company(): void
    {
        $user = $this->officer();
        $employee = Employee::factory()->create();
        $wrongPlan = BenefitPlan::factory()->create();

        $this->actingAs($user)->post(route('admin.employees.benefit-enrollments.store', $employee), [
            'benefit_plan_id' => $wrongPlan->id,
            'effective_date' => '2026-01-01',
        ])->assertSessionHasErrors('benefit_plan_id');
    }

    public function test_re_enrolling_in_the_same_plan_closes_the_prior_row(): void
    {
        $user = $this->officer();
        $employee = Employee::factory()->create();
        $plan = BenefitPlan::factory()->create(['company_id' => $employee->company_id]);
        $first = BenefitEnrollment::factory()->forEmployee($employee)->create([
            'benefit_plan_id' => $plan->id,
            'effective_date' => '2026-01-01',
        ]);

        $this->actingAs($user)->post(route('admin.employees.benefit-enrollments.store', $employee), [
            'benefit_plan_id' => $plan->id,
            'employee_contribution' => 300,
            'effective_date' => '2026-06-01',
        ])->assertRedirect();

        $this->assertSame('2026-05-31', $first->refresh()->end_date->format('Y-m-d'));
        $this->assertSame(2, BenefitEnrollment::count());
        $this->assertSame(1, BenefitEnrollment::whereNull('end_date')->count());
    }

    public function test_concurrent_enrollments_in_different_plans_are_unaffected(): void
    {
        $user = $this->officer();
        $employee = Employee::factory()->create();
        $hmoPlan = BenefitPlan::factory()->create(['company_id' => $employee->company_id]);
        $loanPlan = BenefitPlan::factory()->create(['company_id' => $employee->company_id]);
        BenefitEnrollment::factory()->forEmployee($employee)->create(['benefit_plan_id' => $hmoPlan->id]);

        $this->actingAs($user)->post(route('admin.employees.benefit-enrollments.store', $employee), [
            'benefit_plan_id' => $loanPlan->id,
            'effective_date' => '2026-01-01',
        ])->assertRedirect();

        $this->assertSame(2, BenefitEnrollment::whereNull('end_date')->count());
    }

    public function test_covered_dependents_must_belong_to_the_employee(): void
    {
        $user = $this->officer();
        $employee = Employee::factory()->create();
        $plan = BenefitPlan::factory()->create(['company_id' => $employee->company_id]);
        $otherEmployeesDependent = EmployeeDependent::factory()->create();

        $this->actingAs($user)->post(route('admin.employees.benefit-enrollments.store', $employee), [
            'benefit_plan_id' => $plan->id,
            'effective_date' => '2026-01-01',
            'covered_dependent_ids' => [$otherEmployeesDependent->id],
        ])->assertSessionHasErrors('covered_dependent_ids.0');
    }

    public function test_covered_dependents_are_attached(): void
    {
        $user = $this->officer();
        $employee = Employee::factory()->create();
        $plan = BenefitPlan::factory()->create(['company_id' => $employee->company_id]);
        $dependent = EmployeeDependent::factory()->create(['employee_id' => $employee->id]);

        $this->actingAs($user)->post(route('admin.employees.benefit-enrollments.store', $employee), [
            'benefit_plan_id' => $plan->id,
            'effective_date' => '2026-01-01',
            'covered_dependent_ids' => [$dependent->id],
        ])->assertRedirect();

        $enrollment = BenefitEnrollment::sole();
        $this->assertSame([$dependent->id], $enrollment->coveredDependents->pluck('id')->all());
    }

    public function test_requires_permission(): void
    {
        $plain = User::factory()->create();
        $employee = Employee::factory()->create();

        $this->actingAs($plain)->post(route('admin.employees.benefit-enrollments.store', $employee), [])->assertForbidden();
    }
}
