<?php

namespace App\Enums;

enum CareerDevelopmentPlanStatus: string
{
    case Active = 'active';
    case Achieved = 'achieved';
    case Cancelled = 'cancelled';
}
