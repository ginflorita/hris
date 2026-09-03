<?php

namespace Tests\Feature\Console;

use App\Enums\TrainingEnrollmentStatus;
use App\Models\Employee;
use App\Models\TrainingEnrollment;
use App\Models\User;
use App\Notifications\TrainingCertificateExpiring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendTrainingCertificateExpirationRemindersTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifies_a_linked_employee_thirty_days_before_expiration(): void
    {
        Notification::fake();
        $this->travelTo('2026-06-01');
        $employee = Employee::factory()->create();
        $user = User::factory()->create(['employee_id' => $employee->id]);
        TrainingEnrollment::factory()->forEmployee($employee)->create([
            'status' => TrainingEnrollmentStatus::Completed,
            'certificate_expires_at' => '2026-07-01',
        ]);

        $this->artisan('training:send-certificate-expiration-reminders')->assertSuccessful();

        Notification::assertSentTo($user, TrainingCertificateExpiring::class);
    }

    public function test_notifies_a_linked_employee_seven_days_before_expiration(): void
    {
        Notification::fake();
        $this->travelTo('2026-06-24');
        $employee = Employee::factory()->create();
        $user = User::factory()->create(['employee_id' => $employee->id]);
        TrainingEnrollment::factory()->forEmployee($employee)->create([
            'status' => TrainingEnrollmentStatus::Completed,
            'certificate_expires_at' => '2026-07-01',
        ]);

        $this->artisan('training:send-certificate-expiration-reminders')->assertSuccessful();

        Notification::assertSentTo($user, TrainingCertificateExpiring::class);
    }

    public function test_does_not_notify_outside_the_reminder_windows(): void
    {
        Notification::fake();
        $this->travelTo('2026-06-15');
        $employee = Employee::factory()->create();
        $user = User::factory()->create(['employee_id' => $employee->id]);
        TrainingEnrollment::factory()->forEmployee($employee)->create([
            'status' => TrainingEnrollmentStatus::Completed,
            'certificate_expires_at' => '2026-07-01',
        ]);

        $this->artisan('training:send-certificate-expiration-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_an_unlinked_employee_is_skipped_without_error(): void
    {
        Notification::fake();
        $this->travelTo('2026-06-01');
        $employee = Employee::factory()->create();
        TrainingEnrollment::factory()->forEmployee($employee)->create([
            'status' => TrainingEnrollmentStatus::Completed,
            'certificate_expires_at' => '2026-07-01',
        ]);

        $this->artisan('training:send-certificate-expiration-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_an_enrollment_with_no_certificate_is_ignored(): void
    {
        Notification::fake();
        $this->travelTo('2026-06-01');
        $employee = Employee::factory()->create();
        User::factory()->create(['employee_id' => $employee->id]);
        TrainingEnrollment::factory()->forEmployee($employee)->create([
            'status' => TrainingEnrollmentStatus::NoShow,
            'certificate_expires_at' => null,
        ]);

        $this->artisan('training:send-certificate-expiration-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }
}
