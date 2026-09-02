<?php

namespace App\Enums;

enum SuccessionReadiness: string
{
    case ReadyNow = 'ready_now';
    case Ready1To2Years = 'ready_1_2_years';
    case Ready3To5Years = 'ready_3_5_years';
    case DevelopmentNeeded = 'development_needed';

    /**
     * A plain str_replace('_', ' ', $value) reads as "Ready 1 2 years"
     * for the year-range cases -- worth a real label instead.
     */
    public function label(): string
    {
        return match ($this) {
            self::ReadyNow => 'Ready Now',
            self::Ready1To2Years => 'Ready in 1-2 Years',
            self::Ready3To5Years => 'Ready in 3-5 Years',
            self::DevelopmentNeeded => 'Development Needed',
        };
    }
}
