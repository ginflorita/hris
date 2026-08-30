<?php

namespace Tests\Feature\Admin\Compensation;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\SalaryGrade;
use App\Models\SalaryStructure;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompensationTest extends TestCase
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
        $user->givePermissionTo(['organization.view', 'organization.manage', 'employees.view', 'employees.update']);

        return $user;
    }

    public function test_salary_structure_and_grade_crud(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();

        $this->actingAs($user)->post(route('admin.compensation.structures.store'), [
            'company_id' => $company->id,
            'name' => '2026 Structure',
            'code' => 'SS2026',
            'effective_date' => '2026-01-01',
        ])->assertRedirect(route('admin.compensation.structures.index'));

        $structure = SalaryStructure::sole();
        $this->assertTrue($structure->is_active);

        $this->actingAs($user)->post(route('admin.compensation.grades.store'), [
            'company_id' => $company->id,
            'salary_structure_id' => $structure->id,
            'name' => 'Grade 5',
            'code' => 'G5',
            'min_salary' => 30000,
            'mid_salary' => 40000,
            'max_salary' => 50000,
        ])->assertRedirect(route('admin.compensation.grades.index'));

        $grade = SalaryGrade::sole();
        $this->assertSame($structure->id, $grade->salary_structure_id);

        // Structure can't be deleted while it has a grade.
        $this->actingAs($user)->delete(route('admin.compensation.structures.destroy', $structure))
            ->assertSessionHasErrors('salaryStructure');
    }

    public function test_salary_grade_structure_must_belong_to_the_same_company(): void
    {
        $user = $this->manager();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $structure = SalaryStructure::factory()->for($companyA, 'company')->create();

        $this->actingAs($user)->post(route('admin.compensation.grades.store'), [
            'company_id' => $companyB->id,
            'salary_structure_id' => $structure->id,
            'name' => 'Grade 1',
            'code' => 'G1',
            'min_salary' => 20000,
            'max_salary' => 30000,
        ])->assertSessionHasErrors('salary_structure_id');
    }

    public function test_employment_can_reference_a_salary_grade(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();
        $employee = Employee::factory()->for($company, 'company')->create();
        $grade = SalaryGrade::factory()->for($company, 'company')->create();

        $this->actingAs($user)->post(route('admin.employees.employments.store', $employee), [
            'salary_grade_id' => $grade->id,
            'employment_type' => 'regular',
            'status' => 'active',
            'change_type' => 'hire',
            'effective_date' => '2026-01-01',
            'basic_salary' => 35000,
        ])->assertRedirect();

        $employment = Employment::sole();
        $this->assertSame($grade->id, $employment->salary_grade_id);
    }

    public function test_compensation_item_crud_and_cross_employee_protection(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();
        $employeeA = Employee::factory()->for($company, 'company')->create();
        $employeeB = Employee::factory()->for($company, 'company')->create();

        $this->actingAs($user)->post(route('admin.employees.compensation-items.store', $employeeA), [
            'type' => 'allowance',
            'name' => 'Transportation Allowance',
            'amount' => 2000,
            'frequency' => 'monthly',
            'effective_date' => '2026-01-01',
        ])->assertRedirect();

        $item = $employeeA->compensationItems()->sole();
        $this->assertTrue($item->is_active);

        // Unchecking is_active on update persists (no silent no-op).
        $this->actingAs($user)->put(route('admin.employees.compensation-items.update', [$employeeA, $item]), [
            'type' => 'allowance',
            'name' => 'Transportation Allowance',
            'amount' => 2000,
            'frequency' => 'monthly',
            'effective_date' => '2026-01-01',
        ])->assertRedirect();
        $this->assertFalse($item->fresh()->is_active);

        // A compensation item accessed through the wrong employee 404s.
        $this->actingAs($user)->delete(route('admin.employees.compensation-items.destroy', [$employeeB, $item]))
            ->assertNotFound();
    }

    public function test_without_permission_gets_403(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('admin.compensation.structures.index'))->assertForbidden();
        $this->actingAs($plain)->get(route('admin.compensation.grades.index'))->assertForbidden();
    }
}
