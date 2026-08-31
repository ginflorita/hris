<?php

namespace App\Enums;

enum PayslipAccessAction: string
{
    case Viewed = 'viewed';
    case Downloaded = 'downloaded';
}
