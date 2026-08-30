<?php

namespace Tests\Feature\Admin\Employee;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmploymentTest extends TestCase
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
        $user->givePermissionTo(['employees.view', 'employees.update']);

        return $user;
    }

    public function test_hiring_creates_the_first_employment_record(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();
        $employee = Employee::factory()->for($company, 'company')->create();
        $position = Position::factory()->for($company, 'company')->create();

        $this->actingAs($user)->post(route('admin.employees.employments.store', $employee), [
            'position_id' => $position->id,
            'employment_type' => 'probationary',
            'status' => 'active',
            'change_type' => 'hire',
            'effective_date' => '2026-01-01',
            'basic_salary' => 25000,
        ])->assertRedirect();

        $employment = Employment::sole();
        $this->assertNull($employment->end_date);
        $this->assertTrue($employment->isCurrent());
        $this->assertSame($company->id, $employment->company_id);
        $this->assertSame('25000.00', (string) $employment->basic_salary);
    }

    public function test_a_promotion_closes_the_previous_record_and_opens_a_new_one(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();
        $employee = Employee::factory()->for($company, 'company')->create();
        $juniorRole = Position::factory()->for($company, 'company')->create(['title' => 'Junior Accountant']);
        $seniorRole = Position::factory()->for($company, 'company')->create(['title' => 'Senior Accountant']);

        $this->actingAs($user)->post(route('admin.employees.employments.store', $employee), [
            'position_id' => $juniorRole->id,
            'employment_type' => 'probationary',
            'status' => 'active',
            'change_type' => 'hire',
            'effective_date' => '2024-01-01',
            'basic_salary' => 25000,
        ])->assertRedirect();

        $this->actingAs($user)->post(route('admin.employees.employments.store', $employee), [
            'position_id' => $seniorRole->id,
            'employment_type' => 'regular',
            'status' => 'active',
            'change_type' => 'promotion',
            'effective_date' => '2026-01-01',
            'basic_salary' => 40000,
        ])->assertRedirect();

        $this->assertSame(2, Employment::count());

        $closed = Employment::where('change_type', 'hire')->sole();
        $this->assertSame('2025-12-31', $closed->end_date->format('Y-m-d'));

        $current = $employee->fresh()->currentEmployment;
        $this->assertSame($seniorRole->id, $current->position_id);
        $this->assertSame('40000.00', (string) $current->basic_salary);
        $this->assertTrue($current->isCurrent());
    }

    public function test_department_position_branch_location_manager_must_belong_to_the_employees_company(): void
    {
        $user = $this->manager();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $employee = Employee::factory()->for($companyA, 'company')->create();
        $wrongDepartment = Department::factory()->for($companyB, 'company')->create();

        $this->actingAs($user)->post(route('admin.employees.employments.store', $employee), [
            'department_id' => $wrongDepartment->id,
            'employment_type' => 'regular',
            'status' => 'active',
            'change_type' => 'hire',
            'effective_date' => '2026-01-01',
        ])->assertSessionHasErrors('department_id');

        $this->assertSame(0, Employment::count());
    }

    public function test_an_employee_cannot_be_their_own_manager(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();
        $employee = Employee::factory()->for($company, 'company')->create();

        $this->actingAs($user)->post(route('admin.employees.employments.store', $employee), [
            'manager_id' => $employee->id,
            'employment_type' => 'regular',
            'status' => 'active',
            'change_type' => 'hire',
            'effective_date' => '2026-01-01',
        ])->assertSessionHasErrors('manager_id');

        $this->assertSame(0, Employment::count());
    }

    public function test_separation_sets_status_and_reason(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();
        $employee = Employee::factory()->for($company, 'company')->create();

        $this->actingAs($user)->post(route('admin.employees.employments.store', $employee), [
            'employment_type' => 'regular', 'status' => 'active', 'change_type' => 'hire', 'effective_date' => '2024-01-01',
        ])->assertRedirect();

        $this->actingAs($user)->post(route('admin.employees.employments.store', $employee), [
            'employment_type' => 'regular', 'status' => 'separated', 'change_type' => 'separation',
            'effective_date' => '2026-06-01', 'separation_reason' => 'Resigned',
        ])->assertRedirect();

        $current = $employee->fresh()->currentEmployment;
        $this->assertSame('separated', $current->status->value);
        $this->assertSame('Resigned', $current->separation_reason);
    }

    public function test_without_permission_gets_403(): void
    {
        $plain = User::factory()->create();
        $employee = Employee::factory()->create();

        $this->actingAs($plain)->post(route('admin.employees.employments.store', $employee), [
            'employment_type' => 'regular', 'status' => 'active', 'change_type' => 'hire', 'effective_date' => '2026-01-01',
        ])->assertForbidden();
    }
}
