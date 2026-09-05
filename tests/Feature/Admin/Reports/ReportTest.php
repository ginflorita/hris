<?php

namespace Tests\Feature\Admin\Reports;

use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function viewer(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('reports.view');

        return $user;
    }

    public function test_reports_landing_page_requires_reports_view_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($this->viewer())->get(route('admin.reports.index'))->assertOk();
    }

    public function test_hr_report_requires_reports_view_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.reports.hr.index'))->assertForbidden();
        $this->actingAs($this->viewer())->get(route('admin.reports.hr.index'))->assertOk();
    }

    public function test_hr_report_counts_active_and_archived_employees(): void
    {
        $company = Company::factory()->create();
        Employee::factory()->for($company, 'company')->count(2)->create();
        Employee::factory()->for($company, 'company')->create(['archived_at' => now()]);

        $this->actingAs($this->viewer())->get(route('admin.reports.hr.index'))
            ->assertOk()
            ->assertViewHas('totalActive', 2)
            ->assertViewHas('totalArchived', 1);
    }

    public function test_hr_report_breaks_down_by_department_employment_type_and_status(): void
    {
        $company = Company::factory()->create();
        $department = Department::factory()->for($company, 'company')->create(['name' => 'Engineering']);
        $employee = Employee::factory()->for($company, 'company')->create();
        Employment::factory()->forEmployee($employee)->create([
            'department_id' => $department->id,
            'employment_type' => EmploymentType::Regular,
            'status' => EmploymentStatus::Active,
        ]);

        $this->actingAs($this->viewer())->get(route('admin.reports.hr.index'))
            ->assertOk()
            ->assertViewHas('byDepartment', fn ($rows) => $rows['Engineering'] === 1)
            ->assertViewHas('byEmploymentType', fn ($rows) => $rows['regular'] === 1)
            ->assertViewHas('byStatus', fn ($rows) => $rows['active'] === 1);
    }

    public function test_hr_report_groups_employees_with_no_current_employment_separately(): void
    {
        $company = Company::factory()->create();
        Employee::factory()->for($company, 'company')->create();

        $this->actingAs($this->viewer())->get(route('admin.reports.hr.index'))
            ->assertOk()
            ->assertViewHas('byDepartment', fn ($rows) => $rows['Unassigned'] === 1)
            ->assertViewHas('byEmploymentType', fn ($rows) => $rows['unassigned'] === 1)
            ->assertViewHas('byStatus', fn ($rows) => $rows['no_current_employment'] === 1);
    }

    public function test_hr_report_can_be_filtered_by_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        Employee::factory()->for($companyA, 'company')->create();
        Employee::factory()->for($companyB, 'company')->count(2)->create();

        $this->actingAs($this->viewer())
            ->get(route('admin.reports.hr.index', ['company_id' => $companyA->id]))
            ->assertOk()
            ->assertViewHas('totalActive', 1);
    }
}
