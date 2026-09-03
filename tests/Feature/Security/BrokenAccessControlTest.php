<?php

namespace Tests\Feature\Security;

use App\Enums\DefaultRole;
use App\Enums\PayrollPeriodStatus;
use App\Models\CoeRequest;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\LeaveRequest;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Blueprint §51 17.4 (Broken Access Control Testing) and 17.5 (IDOR /
 * Object-Level Authorization), walked as one consolidated file so a
 * reviewer can check both scenario lists in one place. This is
 * verification, not new coverage in most cases -- RBAC, data scope, and
 * object-level ownership checks were all built as their owning phases
 * landed (see CLAUDE.md's "security is built continuously" principle).
 * Deep behavioral coverage for two of blueprint's named scenarios
 * already exists elsewhere and isn't duplicated here: "Manager -> Other
 * Department" (the enforced granularity is actually Team, not
 * Department -- see CLAUDE.md's Data Scope section for why) is
 * exercised in depth by Tests\Feature\Admin\DataScopeTest, and
 * "Employee -> Other Payslip" already has a dedicated ownership
 * regression in PayslipPortalTest::test_admin_payroll_permissions_do_not
 * _bypass_portal_ownership(). Both get one confirming test here anyway,
 * since the point of this file is a single place that maps directly
 * onto blueprint's own checklist, not just coverage that happens to
 * exist scattered across other files.
 */
class BrokenAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function employeeUser(): User
    {
        $employee = Employee::factory()->create();

        return User::factory()->create(['employee_id' => $employee->id]);
    }

    // -----------------------------------------------------------------
    // 17.4 Broken Access Control Testing
    // -----------------------------------------------------------------

    public function test_employee_role_cannot_reach_admin_employee_records(): void
    {
        $user = $this->employeeUser();
        $user->assignRole(DefaultRole::Employee->value);
        $otherEmployee = Employee::factory()->create();

        // Not even their own admin-side record -- the Employee role holds
        // no employees.view at all, since the portal is the intended
        // surface for self-service, not the admin employee list.
        $this->actingAs($user)->get(route('admin.employees.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.employees.show', $user->employee))->assertForbidden();
        $this->actingAs($user)->get(route('admin.employees.show', $otherEmployee))->assertForbidden();
    }

    public function test_employee_role_cannot_reach_admin_payroll_routes(): void
    {
        $user = $this->employeeUser();
        $user->assignRole(DefaultRole::Employee->value);
        $period = PayrollPeriod::factory()->create();

        $this->actingAs($user)->get(route('admin.payroll.payroll-periods.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.payroll.payroll-periods.show', $period))->assertForbidden();
    }

    public function test_employee_role_cannot_reach_admin_document_routes_for_any_employee(): void
    {
        $user = $this->employeeUser();
        $user->assignRole(DefaultRole::Employee->value);
        $document = EmployeeDocument::factory()->for($user->employee, 'employee')->create();

        // Blocked by the missing employees.view permission before the
        // route-level employee_id ownership check inside the controller
        // is ever reached -- confirmed even against the employee's own
        // document, since the admin surface simply isn't theirs to use.
        $this->actingAs($user)->get(route('admin.employees.documents.download', [$user->employee, $document]))
            ->assertForbidden();
    }

    public function test_hr_staff_cannot_reach_user_or_role_administration(): void
    {
        $hrStaff = User::factory()->create();
        $hrStaff->assignRole(DefaultRole::HrStaff->value);

        $this->actingAs($hrStaff)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($hrStaff)->get(route('admin.roles.index'))->assertForbidden();
        $this->actingAs($hrStaff)->get(route('admin.permissions.index'))->assertForbidden();
    }

    public function test_payroll_administrator_cannot_reach_user_or_role_administration(): void
    {
        $payrollAdmin = User::factory()->create();
        $payrollAdmin->assignRole(DefaultRole::PayrollAdministrator->value);

        $this->actingAs($payrollAdmin)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($payrollAdmin)->get(route('admin.roles.index'))->assertForbidden();
    }

    public function test_manager_cannot_view_an_employee_outside_their_team(): void
    {
        // Full Team-scope coverage lives in Tests\Feature\Admin\DataScopeTest
        // -- this is a single confirming boundary check for blueprint's own
        // "Manager -> Other Department" line item.
        $managerEmployee = Employee::factory()->create();
        $managerUser = User::factory()->create(['employee_id' => $managerEmployee->id]);
        $managerUser->assignRole(DefaultRole::Manager->value);
        $unrelatedEmployee = Employee::factory()->create();

        $this->actingAs($managerUser)->get(route('admin.employees.show', $unrelatedEmployee))->assertForbidden();
    }

    // -----------------------------------------------------------------
    // 17.5 IDOR / Object-Level Authorization
    // -----------------------------------------------------------------

    public function test_employee_cannot_download_another_employees_document_via_portal(): void
    {
        $user = $this->employeeUser();
        $otherEmployee = Employee::factory()->create();
        $otherDocument = EmployeeDocument::factory()->for($otherEmployee, 'employee')->create();

        $this->actingAs($user)->get(route('portal.profile.documents.download', $otherDocument))
            ->assertNotFound();
    }

    public function test_employee_cannot_view_or_download_another_employees_payslip(): void
    {
        $user = $this->employeeUser();
        $otherEmployee = Employee::factory()->create();
        $period = PayrollPeriod::factory()->create(['status' => PayrollPeriodStatus::Published]);
        $otherItem = PayrollItem::factory()->create([
            'employee_id' => $otherEmployee->id,
            'company_id' => $otherEmployee->company_id,
            'payroll_period_id' => $period->id,
        ]);

        $this->actingAs($user)->get(route('portal.payslips.show', $otherItem))->assertNotFound();
        $this->actingAs($user)->get(route('portal.payslips.download', $otherItem))->assertNotFound();
    }

    public function test_employee_cannot_cancel_another_employees_leave_request_via_portal(): void
    {
        $user = $this->employeeUser();
        $otherEmployee = Employee::factory()->create();
        $otherRequest = LeaveRequest::factory()->forEmployee($otherEmployee)->create();

        $this->actingAs($user)->put(route('portal.leave.cancel', $otherRequest))->assertNotFound();
    }

    public function test_employee_cannot_download_another_employees_coe(): void
    {
        $user = $this->employeeUser();
        $otherEmployee = Employee::factory()->create();
        $otherCoe = CoeRequest::factory()->forEmployee($otherEmployee)->approved()->create();

        $this->actingAs($user)->get(route('portal.coe.download', $otherCoe))->assertNotFound();
    }

    public function test_changing_the_id_to_an_owned_record_still_succeeds(): void
    {
        // The negative assertions above are only meaningful paired with a
        // positive control -- confirms the 404s are a real ownership
        // check, not a route/permission failure that would 404 regardless
        // of whose record it is.
        Storage::fake('local');
        $user = $this->employeeUser();
        $ownDocument = EmployeeDocument::factory()->for($user->employee, 'employee')->create([
            'file_path' => 'employee-documents/'.$user->employee_id.'/test.pdf',
        ]);
        Storage::disk('local')->put($ownDocument->file_path, 'fake pdf content');

        $this->actingAs($user)->get(route('portal.profile.documents.download', $ownDocument))->assertOk();
    }
}
