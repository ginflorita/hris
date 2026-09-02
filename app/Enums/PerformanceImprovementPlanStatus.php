<?php

namespace App\Enums;

enum PerformanceImprovementPlanStatus: string
{
    case Active = 'active';
    case Successful = 'successful';
    case Unsuccessful = 'unsuccessful';
    case Cancelled = 'cancelled';

    public function isClosed(): bool
    {
        return $this !== self::Active;
    }
}
