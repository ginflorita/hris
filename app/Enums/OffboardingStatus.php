<?php

namespace App\Enums;

/**
 * Blueprint §26 draws a strict linear pipeline (Resignation -> Approval ->
 * Notice Period -> Clearance -> Asset Return -> Final Payroll -> Final Pay
 * -> COE -> Account Disable -> Separated) with no branching except the
 * ability to abandon the process entirely -- so this enum carries the
 * fixed order itself (sequence()) rather than each transition being
 * reinvented as a separate guarded controller method.
 */
enum OffboardingStatus: string
{
    case Resignation = 'resignation';
    case Approved = 'approved';
    case NoticePeriod = 'notice_period';
    case Clearance = 'clearance';
    case AssetReturn = 'asset_return';
    case FinalPayroll = 'final_payroll';
    case FinalPay = 'final_pay';
    case Coe = 'coe';
    case AccountDisabled = 'account_disabled';
    case Separated = 'separated';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Resignation => 'Resignation',
            self::Approved => 'Approved',
            self::NoticePeriod => 'Notice Period',
            self::Clearance => 'Clearance',
            self::AssetReturn => 'Asset Return',
            self::FinalPayroll => 'Final Payroll',
            self::FinalPay => 'Final Pay',
            self::Coe => 'COE',
            self::AccountDisabled => 'Account Disabled',
            self::Separated => 'Separated',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Separated || $this === self::Cancelled;
    }

    /**
     * The fixed pipeline order, Cancelled excluded (it's an off-ramp
     * reachable from any non-terminal step, not a pipeline stage).
     *
     * @return list<self>
     */
    public static function sequence(): array
    {
        return [
            self::Resignation,
            self::Approved,
            self::NoticePeriod,
            self::Clearance,
            self::AssetReturn,
            self::FinalPayroll,
            self::FinalPay,
            self::Coe,
            self::AccountDisabled,
            self::Separated,
        ];
    }

    public function next(): ?self
    {
        $sequence = self::sequence();
        $index = array_search($this, $sequence, true);

        return $index === false ? null : ($sequence[$index + 1] ?? null);
    }
}
