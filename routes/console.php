<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Blueprint §47 Scheduler. Each command self-gates which policies/
// enrollments are actually due today -- see their own docblocks --
// so a daily cadence is correct even for Monthly/Annually accrual.
Schedule::command('leave:accrue')->dailyAt('01:00');
Schedule::command('leave:carry-over')->yearlyOn(1, 1, '01:30');
Schedule::command('training:send-certificate-expiration-reminders')->dailyAt('07:00');
