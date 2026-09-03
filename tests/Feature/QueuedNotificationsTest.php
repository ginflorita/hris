<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\TrainingEnrollment;
use App\Notifications\PayslipPublished;
use App\Notifications\SecurityAlert;
use App\Notifications\TrainingCertificateExpiring;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blueprint §46 Queue Processing lists email/bulk notifications among
 * what should be queued. All three of this app's Notification classes
 * now implement ShouldQueue (18b) -- this pins that down as a real
 * regression rather than trusting the `implements` clause never gets
 * quietly dropped later. Not tested here: that Laravel's own queue
 * system actually dispatches a ShouldQueue notification via a queued
 * job -- that's the framework's own well-tested behavior, not this
 * app's code, the same reasoning 17d already applied to skip a
 * CSRF-rejection test.
 */
class QueuedNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_alert_is_queued(): void
    {
        $this->assertInstanceOf(ShouldQueue::class, new SecurityAlert('Test alert.'));
    }

    public function test_payslip_published_is_queued(): void
    {
        $item = PayrollItem::factory()->create();

        $this->assertInstanceOf(ShouldQueue::class, new PayslipPublished($item));
    }

    public function test_training_certificate_expiring_is_queued(): void
    {
        $employee = Employee::factory()->create();
        $enrollment = TrainingEnrollment::factory()->forEmployee($employee)->create();

        $this->assertInstanceOf(ShouldQueue::class, new TrainingCertificateExpiring($enrollment, 30));
    }
}
