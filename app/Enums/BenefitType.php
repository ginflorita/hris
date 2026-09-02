<?php

namespace App\Enums;

/**
 * Blueprint §21 lists 8 "Support" types: SSS, PhilHealth, Pag-IBIG, HMO,
 * Insurance, Allowances, Loans, Retirement benefits. Only the last four
 * are new here -- SSS/PhilHealth/Pag-IBIG are government contributions
 * already fully modeled by ContributionRateTable + PayrollItemContribution
 * (Phase 11), and Allowances already exist as a CompensationItem type
 * (Phase 10). Rebuilding either as a BenefitPlan type would create a
 * second, disconnected way to record the same thing payroll already
 * computes every period.
 */
enum BenefitType: string
{
    case Hmo = 'hmo';
    case Insurance = 'insurance';
    case Loan = 'loan';
    case Retirement = 'retirement';

    public function label(): string
    {
        return match ($this) {
            self::Hmo => 'HMO',
            self::Insurance => 'Insurance',
            self::Loan => 'Loan',
            self::Retirement => 'Retirement',
        };
    }
}
