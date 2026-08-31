<?php

namespace Tests\Feature\Portal;

use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\CoeRequest;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\OvertimeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestAggregationTest extends TestCase
{
    use RefreshDatabase;

    private function employeeUser(): User
    {
        $employee = Employee::factory()->create();

        return User::factory()->create(['employee_id' => $employee->id]);
    }

    public function test_lists_all_four_request_types_for_the_employee_only(): void
    {
        $user = $this->employeeUser();
        $employee = $user->employee;
        $leaveType = LeaveType::factory()->for($employee->company, 'company')->create(['name' => 'Vacation']);
        LeaveRequest::factory()->forEmployee($employee)->create(['leave_type_id' => $leaveType->id, 'days_count' => 2]);
        OvertimeRequest::factory()->forEmployee($employee)->create();
        $attendance = Attendance::factory()->forEmployee($employee)->create();
        AttendanceCorrectionRequest::factory()->forAttendance($attendance)->create();
        CoeRequest::factory()->forEmployee($employee)->create();

        // Someone else's requests must never appear.
        $stranger = Employee::factory()->create();
        LeaveRequest::factory()->forEmployee($stranger)->create();

        $response = $this->actingAs($user)->get(route('portal.requests.index'));

        $response->assertOk();
        $types = $response->viewData('requests')->pluck('type')->all();
        $this->assertContains('Leave', $types);
        $this->assertContains('Overtime', $types);
        $this->assertContains('Attendance Correction', $types);
        $this->assertContains('Certificate of Employment', $types);
        $this->assertCount(4, $types);
    }

    public function test_unlinked_account_sees_a_friendly_message(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('portal.requests.index'));

        $response->assertOk()->assertSee("isn't linked to an employee record", false);
    }

    public function test_empty_state_when_no_requests_exist(): void
    {
        $user = $this->employeeUser();

        $response = $this->actingAs($user)->get(route('portal.requests.index'));

        $response->assertOk()->assertSee('No requests yet.');
    }
}
