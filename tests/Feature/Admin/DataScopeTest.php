<?php

namespace Tests\Feature\Admin;

use App\Enums\DefaultRole;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\OvertimeRequest;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Exercises the seeded Manager role's Team data_scope end to end via
 * App\Domain\Security\Services\DataScopeResolver -- these tests assign
 * the *real* Manager role (assignRole) rather than granting permissions
 * directly, since a directly-granted permission carries no data_scope
 * and is (correctly) unrestricted. See DataScopeResolver's own docblock.
 */
class DataScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function managerWithReport(): array
    {
        $managerEmployee = Employee::factory()->create();
        $managerUser = User::factory()->create(['employee_id' => $managerEmployee->id]);
        $managerUser->assignRole(DefaultRole::Manager->value);

        $report = Employee::factory()->for($managerEmployee->company, 'company')->create();
        Employment::factory()->forEmployee($report)->create(['manager_id' => $managerEmployee->id]);

        $stranger = Employee::factory()->create();
        Employment::factory()->forEmployee($stranger)->create();

        return [$managerUser, $managerEmployee, $report, $stranger];
    }

    public function test_manager_sees_only_direct_reports_in_employee_index(): void
    {
        [$managerUser, , $report, $stranger] = $this->managerWithReport();

        $response = $this->actingAs($managerUser)->get(route('admin.employees.index'));

        $response->assertOk()->assertSee($report->full_name)->assertDontSee($stranger->full_name);
    }

    public function test_manager_cannot_view_a_non_reports_profile(): void
    {
        [$managerUser, , $report, $stranger] = $this->managerWithReport();

        $this->actingAs($managerUser)->get(route('admin.employees.show', $report))->assertOk();
        $this->actingAs($managerUser)->get(route('admin.employees.show', $stranger))->assertForbidden();
    }

    public function test_manager_only_sees_and_approves_their_teams_leave_requests(): void
    {
        [$managerUser, , $report, $stranger] = $this->managerWithReport();
        $leaveType = LeaveType::factory()->for($report->company, 'company')->create();
        $teamRequest = LeaveRequest::factory()->forEmployee($report)->create(['leave_type_id' => $leaveType->id]);
        $strangerRequest = LeaveRequest::factory()->forEmployee($stranger)->create();

        $indexResponse = $this->actingAs($managerUser)->get(route('admin.leave.requests.index'));
        $indexResponse->assertOk();
        $visibleIds = $indexResponse->viewData('leaveRequests')->pluck('id')->all();
        $this->assertContains($teamRequest->id, $visibleIds);
        $this->assertNotContains($strangerRequest->id, $visibleIds);

        $this->actingAs($managerUser)->put(route('admin.leave.requests.approve', $strangerRequest))->assertForbidden();
        $this->actingAs($managerUser)->put(route('admin.leave.requests.approve', $teamRequest))->assertRedirect();

        $this->assertSame('approved', $teamRequest->refresh()->status->value);
        $this->assertSame('pending', $strangerRequest->refresh()->status->value);
    }

    public function test_manager_can_approve_teams_overtime_via_the_narrow_permission_only(): void
    {
        [$managerUser, , $report, $stranger] = $this->managerWithReport();
        $teamOvertime = OvertimeRequest::factory()->forEmployee($report)->create();
        $strangerOvertime = OvertimeRequest::factory()->forEmployee($stranger)->create();

        $this->actingAs($managerUser)->put(route('admin.attendance.overtime.approve', $strangerOvertime))->assertForbidden();
        $this->actingAs($managerUser)->put(route('admin.attendance.overtime.approve', $teamOvertime))->assertRedirect();

        $this->assertSame('approved', $teamOvertime->refresh()->status->value);

        // The Manager role does NOT hold attendance.manage -- shift/schedule/
        // holiday management and manual attendance entry stay out of reach
        // (index is attendance.view-gated and Manager has that; create()
        // is attendance.manage-gated, which is the actual boundary).
        $this->actingAs($managerUser)->get(route('admin.attendance.shifts.create'))->assertForbidden();
    }

    public function test_manager_sees_only_teams_attendance(): void
    {
        [$managerUser, , $report, $stranger] = $this->managerWithReport();
        Attendance::factory()->forEmployee($report)->create(['date' => '2026-02-01']);
        Attendance::factory()->forEmployee($stranger)->create(['date' => '2026-02-01']);

        $response = $this->actingAs($managerUser)->get(route('admin.attendance.attendances.index'));

        $response->assertOk();
        $visibleIds = $response->viewData('attendances')->pluck('employee_id')->all();
        $this->assertContains($report->id, $visibleIds);
        $this->assertNotContains($stranger->id, $visibleIds);
    }

    public function test_company_scoped_roles_are_unaffected_by_team_scope(): void
    {
        $hrUser = User::factory()->create();
        $hrUser->assignRole(DefaultRole::HrAdministrator->value);
        $unrelatedEmployee = Employee::factory()->create();

        $this->actingAs($hrUser)->get(route('admin.employees.index'))
            ->assertOk()->assertSee($unrelatedEmployee->full_name);
        $this->actingAs($hrUser)->get(route('admin.employees.show', $unrelatedEmployee))->assertOk();
    }

    public function test_attendance_officer_keeps_unrestricted_overtime_approval_via_attendance_manage(): void
    {
        $officer = User::factory()->create();
        $officer->assignRole(DefaultRole::AttendanceOfficer->value);
        $unrelatedOvertime = OvertimeRequest::factory()->create();

        $this->actingAs($officer)->put(route('admin.attendance.overtime.approve', $unrelatedOvertime))->assertRedirect();
        $this->assertSame('approved', $unrelatedOvertime->refresh()->status->value);
    }

    public function test_manager_with_no_direct_reports_sees_an_empty_team(): void
    {
        $managerEmployee = Employee::factory()->create();
        $managerUser = User::factory()->create(['employee_id' => $managerEmployee->id]);
        $managerUser->assignRole(DefaultRole::Manager->value);
        $someoneElse = Employee::factory()->create();

        $response = $this->actingAs($managerUser)->get(route('admin.employees.index'));

        $response->assertOk()->assertDontSee($someoneElse->full_name);
    }
}
