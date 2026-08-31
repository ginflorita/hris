<?php

namespace App\Enums;

/**
 * Blueprint §25 "Support": the four COE variants a request can be. Only
 * WithCompensation puts salary on the generated certificate -- see
 * CoeRequestController::approve() and the coe-pdf template.
 */
enum CoeRequestType: string
{
    case Standard = 'standard';
    case WithCompensation = 'with_compensation';
    case WithoutCompensation = 'without_compensation';
    case EmploymentVerification = 'employment_verification';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard COE',
            self::WithCompensation => 'COE with Compensation',
            self::WithoutCompensation => 'COE without Compensation',
            self::EmploymentVerification => 'Employment Verification',
        };
    }
}
