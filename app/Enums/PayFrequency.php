<?php

namespace App\Enums;

enum PayFrequency: string
{
    case Weekly = 'weekly';
    case BiWeekly = 'biweekly';
    case SemiMonthly = 'semi_monthly';
    case Monthly = 'monthly';

    /**
     * How many pay periods this frequency produces in a year -- the
     * standard annualized-periods convention (used the same way real
     * payroll systems derive a per-period rate from an annual one).
     * PayrollCalculationService uses 12 / periodsPerYear() to prorate a
     * monthly figure (basic salary, a Monthly CompensationItem) down to
     * one period, deliberately without a "standard working days/hours"
     * divisor -- see CLAUDE.md "Payroll" for why that stays undone.
     */
    public function periodsPerYear(): int
    {
        return match ($this) {
            self::Monthly => 12,
            self::SemiMonthly => 24,
            self::BiWeekly => 26,
            self::Weekly => 52,
        };
    }
}
