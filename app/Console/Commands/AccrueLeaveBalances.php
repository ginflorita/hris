<?php

namespace App\Console\Commands;

use App\Domain\Leave\Services\LeaveBalanceService;
use App\Enums\AccrualFrequency;
use App\Enums\LeaveTransactionType;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeavePolicy;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Blueprint §47 Scheduler: "Leave accrual" -- closes the gap CLAUDE.md's
 * Leave section documented since Phase 9 ("nothing runs them on a
 * cron"). Runs daily; self-gates per policy against today's date rather
 * than relying on the cron cadence alone, since Monthly and Annually
 * policies both need to fire on a *daily* schedule but only actually
 * accrue on specific days.
 *
 * PerPayPeriod is a deliberate, documented gap, not silently skipped:
 * correctly firing it needs each employee's own PayrollGroup pay
 * frequency (weekly/biweekly/semi-monthly/monthly, set on Employment,
 * not on LeavePolicy) to know which days are actually period
 * boundaries -- effectively re-deriving Payroll's own period logic.
 * Building that without a concrete requirement for how it should align
 * with actual PayrollPeriod processing would be guessing, the same
 * restraint this app already applies to overtime/holiday pay rates.
 */
class AccrueLeaveBalances extends Command
{
    protected $signature = 'leave:accrue';

    protected $description = 'Apply scheduled leave accrual for every active policy whose frequency is due today.';

    public function handle(LeaveBalanceService $service): int
    {
        $today = Carbon::today();
        $accrued = 0;

        foreach (LeavePolicy::query()->where('is_active', true)->get() as $policy) {
            if (! $this->isDueToday($policy->accrual_frequency, $today)) {
                continue;
            }

            $employees = Employee::query()
                ->where('company_id', $policy->company_id)
                ->whereNull('archived_at')
                ->get();

            foreach ($employees as $employee) {
                $currentBalance = (float) (LeaveBalance::query()
                    ->where('employee_id', $employee->id)
                    ->where('leave_type_id', $policy->leave_type_id)
                    ->value('balance') ?? 0);

                $amount = (float) $policy->accrual_rate;
                if ($policy->max_balance !== null) {
                    $amount = min($amount, max(0, (float) $policy->max_balance - $currentBalance));
                }

                if ($amount <= 0) {
                    continue;
                }

                $service->applyTransaction(
                    employee: $employee,
                    leaveType: $policy->leaveType,
                    type: LeaveTransactionType::Accrual,
                    amount: $amount,
                    date: $today->toDateString(),
                    reason: "Scheduled {$policy->accrual_frequency->value} accrual ({$policy->name})",
                );
                $accrued++;
            }
        }

        $this->info("Applied leave accrual to {$accrued} employee/leave-type balance(s).");

        return self::SUCCESS;
    }

    private function isDueToday(AccrualFrequency $frequency, Carbon $today): bool
    {
        return match ($frequency) {
            AccrualFrequency::Monthly => $today->day === 1,
            AccrualFrequency::Annually => $today->month === 1 && $today->day === 1,
            AccrualFrequency::PerPayPeriod => false,
        };
    }
}
