<?php

namespace Tests\Feature\Portal;

use App\Enums\AttendanceCorrectionRequestStatus;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    private function employeeUser(): User
    {
        $employee = Employee::factory()->create();

        return User::factory()->create(['employee_id' => $employee->id]);
    }

    public function test_employee_can_request_a_correction_for_their_own_attendance(): void
    {
        $user = $this->employeeUser();
        $attendance = Attendance::factory()->forEmployee($user->employee)->create(['status' => 'present']);

        $this->actingAs($user)->post(route('portal.attendance.correction-requests.store', $attendance), [
            'requested_time_in' => '08:15',
            'requested_time_out' => '17:00',
            'requested_status' => 'late',
            'reason' => 'Traffic caused a late clock-in, requesting the correct time be recorded.',
        ])->assertRedirect();

        $request = AttendanceCorrectionRequest::sole();
        $this->assertSame($attendance->id, $request->attendance_id);
        $this->assertSame($user->employee_id, $request->employee_id);
        $this->assertSame($user->id, $request->requested_by);
        $this->assertSame(AttendanceCorrectionRequestStatus::Pending, $request->status);

        // The underlying attendance row is untouched until an admin approves.
        $attendance->refresh();
        $this->assertSame('present', $attendance->status->value);
    }

    public function test_employee_cannot_request_a_correction_for_another_employees_attendance(): void
    {
        $user = $this->employeeUser();
        $otherAttendance = Attendance::factory()->create();

        $this->actingAs($user)->post(route('portal.attendance.correction-requests.store', $otherAttendance), [
            'requested_status' => 'present',
            'reason' => 'test',
        ])->assertNotFound();

        $this->assertSame(0, AttendanceCorrectionRequest::count());
    }

    public function test_unlinked_account_cannot_submit(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create();

        $this->actingAs($user)->post(route('portal.attendance.correction-requests.store', $attendance), [
            'requested_status' => 'present',
            'reason' => 'test',
        ])->assertNotFound();
    }

    public function test_index_shows_own_attendance_and_correction_history(): void
    {
        $user = $this->employeeUser();
        $attendance = Attendance::factory()->forEmployee($user->employee)->create(['date' => '2026-02-10']);
        AttendanceCorrectionRequest::factory()->forAttendance($attendance)->rejected()->create([
            'rejection_reason' => 'Not enough evidence provided.',
        ]);

        $response = $this->actingAs($user)->get(route('portal.attendance.index'));

        $response->assertOk()
            ->assertSee('Feb 10, 2026')
            ->assertSee('Not enough evidence provided.');
    }

    public function test_a_pending_request_shows_as_pending_instead_of_the_request_button(): void
    {
        $user = $this->employeeUser();
        $attendance = Attendance::factory()->forEmployee($user->employee)->create();
        AttendanceCorrectionRequest::factory()->forAttendance($attendance)->create();

        $response = $this->actingAs($user)->get(route('portal.attendance.index'));

        $response->assertOk()->assertSee('Correction pending');
    }
}
