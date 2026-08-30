<?php

namespace App\Enums;

enum CompensationFrequency: string
{
    case OneTime = 'one_time';
    case Monthly = 'monthly';
    case Annual = 'annual';
}
