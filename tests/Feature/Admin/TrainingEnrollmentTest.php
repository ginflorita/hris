<?php

namespace Tests\Feature\Admin;

use App\Enums\TrainingEnrollmentStatus;
use App\Models\Employee;
use App\Models\TrainingEnrollment;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingEnrollmentTest extends TestCase
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
        $user->givePermissionTo(['training.view', 'training.manage']);

        return $user;
    }

    public function test_enrolling_an_employee(): void
    {
        $user = $this->officer();
        $session = TrainingSession::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $session->company_id]);

        $this->actingAs($user)->post(route('admin.training.courses.sessions.enrollments.store', [$session->course, $session]), [
            'employee_id' => $employee->id,
        ])->assertRedirect();

        $enrollment = TrainingEnrollment::sole();
        $this->assertSame($employee->id, $enrollment->employee_id);
        $this->assertSame(TrainingEnrollmentStatus::Enrolled, $enrollment->status);
    }

    public function test_employee_must_belong_to_the_sessions_company(): void
    {
        $user = $this->officer();
        $session = TrainingSession::factory()->create();
        $wrongEmployee = Employee::factory()->create();

        $this->actingAs($user)->post(route('admin.training.courses.sessions.enrollments.store', [$session->course, $session]), [
            'employee_id' => $wrongEmployee->id,
        ])->assertSessionHasErrors('employee_id');
    }

    public function test_cannot_enroll_the_same_employee_twice(): void
    {
        $user = $this->officer();
        $session = TrainingSession::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $session->company_id]);
        TrainingEnrollment::factory()->forSession($session)->forEmployee($employee)->create();

        $this->actingAs($user)->post(route('admin.training.courses.sessions.enrollments.store', [$session->course, $session]), [
            'employee_id' => $employee->id,
        ])->assertSessionHasErrors('employee_id');

        $this->assertSame(1, TrainingEnrollment::count());
    }

    public function test_enrollment_is_blocked_once_the_session_is_at_capacity(): void
    {
        $user = $this->officer();
        $session = TrainingSession::factory()->create(['capacity' => 1]);
        $enrolled = Employee::factory()->create(['company_id' => $session->company_id]);
        TrainingEnrollment::factory()->forSession($session)->forEmployee($enrolled)->create();
        $nextEmployee = Employee::factory()->create(['company_id' => $session->company_id]);

        $this->actingAs($user)->post(route('admin.training.courses.sessions.enrollments.store', [$session->course, $session]), [
            'employee_id' => $nextEmployee->id,
        ])->assertSessionHasErrors('employee_id');

        $this->assertSame(1, TrainingEnrollment::count());
    }

    public function test_a_cancelled_enrollment_frees_its_seat(): void
    {
        $user = $this->officer();
        $session = TrainingSession::factory()->create(['capacity' => 1]);
        $cancelledEmployee = Employee::factory()->create(['company_id' => $session->company_id]);
        TrainingEnrollment::factory()->forSession($session)->forEmployee($cancelledEmployee)
            ->create(['status' => TrainingEnrollmentStatus::Cancelled]);
        $nextEmployee = Employee::factory()->create(['company_id' => $session->company_id]);

        $this->actingAs($user)->post(route('admin.training.courses.sessions.enrollments.store', [$session->course, $session]), [
            'employee_id' => $nextEmployee->id,
        ])->assertRedirect()->assertSessionDoesntHaveErrors();
    }

    public function test_recording_completion_with_a_certificate(): void
    {
        $user = $this->officer();
        $session = TrainingSession::factory()->create();
        $enrollment = TrainingEnrollment::factory()->forSession($session)->create();

        $this->actingAs($user)->put(route('admin.training.courses.sessions.enrollments.update', [$session->course, $session, $enrollment]), [
            'status' => 'completed',
            'certificate_number' => 'CERT-001',
            'certificate_issued_at' => '2026-06-05',
            'certificate_expires_at' => '2028-06-05',
        ])->assertRedirect();

        $enrollment->refresh();
        $this->assertSame(TrainingEnrollmentStatus::Completed, $enrollment->status);
        $this->assertSame('CERT-001', $enrollment->certificate_number);
    }

    public function test_certificate_fields_are_ignored_unless_completed(): void
    {
        $user = $this->officer();
        $session = TrainingSession::factory()->create();
        $enrollment = TrainingEnrollment::factory()->forSession($session)->create();

        $this->actingAs($user)->put(route('admin.training.courses.sessions.enrollments.update', [$session->course, $session, $enrollment]), [
            'status' => 'no_show',
            'certificate_number' => 'CERT-001',
        ])->assertRedirect();

        $enrollment->refresh();
        $this->assertSame(TrainingEnrollmentStatus::NoShow, $enrollment->status);
        $this->assertNull($enrollment->certificate_number);
    }

    public function test_a_decided_enrollment_cannot_be_decided_again(): void
    {
        $user = $this->officer();
        $session = TrainingSession::factory()->create();
        $enrollment = TrainingEnrollment::factory()->forSession($session)->create(['status' => TrainingEnrollmentStatus::Completed]);

        $this->actingAs($user)->put(route('admin.training.courses.sessions.enrollments.update', [$session->course, $session, $enrollment]), [
            'status' => 'cancelled',
        ])->assertStatus(422);
    }

    public function test_a_decided_enrollment_cannot_be_removed(): void
    {
        $user = $this->officer();
        $session = TrainingSession::factory()->create();
        $enrollment = TrainingEnrollment::factory()->forSession($session)->create(['status' => TrainingEnrollmentStatus::Completed]);

        $this->actingAs($user)->delete(route('admin.training.courses.sessions.enrollments.destroy', [$session->course, $session, $enrollment]))
            ->assertStatus(422);
    }

    public function test_an_enrollment_from_another_session_cannot_be_acted_on_through_this_one(): void
    {
        $user = $this->officer();
        $sessionA = TrainingSession::factory()->create();
        $sessionB = TrainingSession::factory()->create();
        $enrollment = TrainingEnrollment::factory()->forSession($sessionB)->create();

        $this->actingAs($user)->put(route('admin.training.courses.sessions.enrollments.update', [$sessionA->course, $sessionA, $enrollment]), [
            'status' => 'completed',
        ])->assertNotFound();
    }

    public function test_session_show_page_lists_the_roster(): void
    {
        $user = $this->officer();
        $session = TrainingSession::factory()->create();
        $enrollment = TrainingEnrollment::factory()->forSession($session)->create();

        $this->actingAs($user)->get(route('admin.training.courses.sessions.show', [$session->course, $session]))
            ->assertOk()
            ->assertSee($enrollment->employee->full_name);
    }

    public function test_requires_permission(): void
    {
        $plain = User::factory()->create();
        $session = TrainingSession::factory()->create();

        $this->actingAs($plain)->post(route('admin.training.courses.sessions.enrollments.store', [$session->course, $session]), [])
            ->assertForbidden();
    }
}
