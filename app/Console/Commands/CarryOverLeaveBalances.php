<?php

namespace App\Console\Commands;

use App\Domain\Leave\Services\LeaveBalanceService;
use App\Enums\LeaveTransactionType;
use App\Models\LeaveBalance;
use App\Models\LeavePolicy;
use Illuminate\Console\Command;

/**
 * Blueprint §47's "Leave accrual" bullet pairs naturally with
 * LeaveTransactionType::CarryOver, already reserved in the ledger's
 * vocabulary since Phase 9 for exactly this job. Scheduled once a year
 * (routes/console.php), not self-gated by date the way
 * AccrueLeaveBalances is -- carry-over only has one frequency, so the
 * schedule entry's own yearlyOn() is the only date check needed.
 *
 * A policy's carry_over_days is the *cap* on what survives into the new
 * year, not an amount to add -- any balance above it is forfeited
 * (logged as a negative CarryOver transaction), any balance at or below
 * it is left untouched.
 */
class CarryOverLeaveBalances extends Command
{
    protected $signature = 'leave:carry-over';

    protected $description = "Cap each employee's leave balance to their policy's year-end carry-over limit, forfeiting the excess.";

    public function handle(LeaveBalanceService $service): int
    {
        $forfeited = 0;

        $policies = LeavePolicy::query()
            ->where('is_active', true)
            ->whereNotNull('carry_over_days')
            ->get();

        foreach ($policies as $policy) {
            $balances = LeaveBalance::query()
                ->where('leave_type_id', $policy->leave_type_id)
                ->where('balance', '>', $policy->carry_over_days)
                ->whereHas('employee', fn ($query) => $query
                    ->where('company_id', $policy->company_id)
                    ->whereNull('archived_at'))
                ->with('employee')
                ->get();

            foreach ($balances as $balance) {
                $excess = round((float) $balance->balance - (float) $policy->carry_over_days, 2);

                $service->applyTransaction(
                    employee: $balance->employee,
                    leaveType: $policy->leaveType,
                    type: LeaveTransactionType::CarryOver,
                    amount: -$excess,
                    date: now()->toDateString(),
                    reason: "Year-end carry-over cap: forfeited {$excess} day(s) over the {$policy->carry_over_days}-day limit ({$policy->name})",
                );
                $forfeited++;
            }
        }

        $this->info("Applied the year-end carry-over cap to {$forfeited} balance(s).");

        return self::SUCCESS;
    }
}
