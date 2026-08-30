<?php

namespace App\Enums;

enum AccrualFrequency: string
{
    case Monthly = 'monthly';
    case Annually = 'annually';
    case PerPayPeriod = 'per_pay_period';
}
