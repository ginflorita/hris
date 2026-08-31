<?php

namespace App\Enums;

enum PayFrequency: string
{
    case Weekly = 'weekly';
    case BiWeekly = 'biweekly';
    case SemiMonthly = 'semi_monthly';
    case Monthly = 'monthly';
}
