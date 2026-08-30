<?php

namespace App\Enums;

enum CompensationItemType: string
{
    case Allowance = 'allowance';
    case Bonus = 'bonus';
    case Incentive = 'incentive';
}
