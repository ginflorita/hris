<?php

namespace App\Console\Commands;

use App\Enums\TrainingEnrollmentStatus;
use App\Models\TrainingEnrollment;
use App\Notifications\TrainingCertificateExpiring;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Blueprint §23's "Expiration reminders" bullet, documented in
 * CLAUDE.md's 15f section as a deliberate gap waiting for "whenever
 * this app grows a job-scheduling story for the first time." Fires
 * once at each of two thresholds (30 and 7 days before expiration) by
 * matching on the exact target date rather than a "less than N days"
 * range -- that naturally sends each reminder exactly once per
 * enrollment without needing a separate "already reminded" column to
 * track.
 */
class SendTrainingCertificateExpirationReminders extends Command
{
    protected $signature = 'training:send-certificate-expiration-reminders';

    protected $description = 'Notify employees whose training certificate expires in 30 or 7 days.';

    private const REMINDER_DAYS_OUT = [30, 7];

    public function handle(): int
    {
        $sent = 0;

        foreach (self::REMINDER_DAYS_OUT as $daysOut) {
            $targetDate = Carbon::today()->addDays($daysOut);

            $enrollments = TrainingEnrollment::query()
                ->where('status', TrainingEnrollmentStatus::Completed)
                ->whereDate('certificate_expires_at', $targetDate)
                ->with('employee.user', 'session.course')
                ->get();

            foreach ($enrollments as $enrollment) {
                $user = $enrollment->employee->user;

                if (! $user) {
                    continue;
                }

                $user->notify(new TrainingCertificateExpiring($enrollment, $daysOut));
                $sent++;
            }
        }

        $this->info("Sent {$sent} certificate expiration reminder(s).");

        return self::SUCCESS;
    }
}
