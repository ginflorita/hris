<?php

namespace App\Enums;

/**
 * Blueprint §8: Job Offer is the pipeline step between Final Interview and
 * Hired. A Pending offer is either Accepted (unlocking hiring conversion --
 * see JobOfferController::convert()), Declined by the candidate, or
 * Rescinded by HR before the candidate responds.
 */
enum JobOfferStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Rescinded = 'rescinded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Accepted => 'Accepted',
            self::Declined => 'Declined',
            self::Rescinded => 'Rescinded',
        };
    }
}
