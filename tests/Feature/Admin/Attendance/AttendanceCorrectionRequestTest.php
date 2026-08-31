<?php

namespace Tests\Feature\Admin\Attendance;

use App\Enums\AttendanceCorrectionRequestStatus;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionRequestTest extends TestCase
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
        $user->givePermissionTo(['attendance.view', 'attendance.manage', 'attendance.correct']);

        return $user;
    }

    public function test_approving_applies_the_correction_through_the_shared_service(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $attendance = Attendance::factory()->forEmployee($employee)->create([
            'date' => '2026-01-05', 'time_in' => '2026-01-05 08:00:00', 'time_out' => '2026-01-05 17:00:00', 'status' => 'present',
        ]);
        $correctionRequest = AttendanceCorrectionRequest::factory()->forAttendance($attendance)->create([
            'requested_time_in' => '08:30',
            'requested_time_out' => '17:00',
            'requested_status' => 'late',
            'reason' => 'Forgot to clock in on time.',
        ]);

        $this->actingAs($user)->put(route('admin.attendance.correction-requests.approve', $correctionRequest))
            ->assertRedirect();

        $attendance->refresh();
        $this->assertSame('08:30', $attendance->time_in->format('H:i'));
        $this->assertSame('late', $attendance->status->value);
        $this->assertTrue($attendance->is_corrected);
        $this->assertSame($user->id, $attendance->corrected_by);

        // Same audit trail as a direct HR correction.
        $timeInLog = $attendance->correctionLogs->firstWhere('field', 'time_in');
        $this->assertNotNull($timeInLog);
        $this->assertSame('08:00', $timeInLog->old_value);
        $this->assertSame('08:30', $timeInLog->new_value);
        $this->assertStringContainsString('Forgot to clock in on time.', $timeInLog->reason);

        $correctionRequest->refresh();
        $this->assertSame(AttendanceCorrectionRequestStatus::Approved, $correctionRequest->status);
        $this->assertSame($user->id, $correctionRequest->approved_by);
        $this->assertNotNull($correctionRequest->approved_at);
    }

    public function test_rejecting_requires_a_reason_and_leaves_attendance_untouched(): void
    {
        $user = $this->manager();
        $attendance = Attendance::factory()->create(['status' => 'present']);
        $correctionRequest = AttendanceCorrectionRequest::factory()->forAttendance($attendance)->create();

        $this->actingAs($user)->put(route('admin.attendance.correction-requests.reject', $correctionRequest), [])
            ->assertSessionHasErrors('rejection_reason');

        $this->actingAs($user)->put(route('admin.attendance.correction-requests.reject', $correctionRequest), [
            'rejection_reason' => 'Timecard from security desk does not match.',
        ])->assertRedirect();

        $correctionRequest->refresh();
        $this->assertSame(AttendanceCorrectionRequestStatus::Rejected, $correctionRequest->status);
        $this->assertSame('Timecard from security desk does not match.', $correctionRequest->rejection_reason);

        $attendance->refresh();
        $this->assertSame('present', $attendance->status->value);
        $this->assertFalse($attendance->is_corrected);
    }

    public function test_only_a_pending_request_can_be_approved_or_rejected(): void
    {
        $user = $this->manager();
        $attendance = Attendance::factory()->create();
        $approved = AttendanceCorrectionRequest::factory()->forAttendance($attendance)->approved()->create();

        $this->actingAs($user)->put(route('admin.attendance.correction-requests.approve', $approved))
            ->assertStatus(422);

        $this->actingAs($user)->put(route('admin.attendance.correction-requests.reject', $approved), [
            'rejection_reason' => 'test',
        ])->assertStatus(422);
    }

    public function test_correction_requests_require_the_correct_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['attendance.view', 'attendance.manage']);
        $attendance = Attendance::factory()->create();
        $correctionRequest = AttendanceCorrectionRequest::factory()->forAttendance($attendance)->create();

        $this->actingAs($user)->get(route('admin.attendance.correction-requests.index'))->assertForbidden();
        $this->actingAs($user)->put(route('admin.attendance.correction-requests.approve', $correctionRequest))->assertForbidden();
    }

    public function test_index_filters_by_status(): void
    {
        $user = $this->manager();
        AttendanceCorrectionRequest::factory()->create();
        AttendanceCorrectionRequest::factory()->approved()->create();

        $response = $this->actingAs($user)->get(route('admin.attendance.correction-requests.index', ['status' => 'approved']));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'Approved</span>'));
    }
}
