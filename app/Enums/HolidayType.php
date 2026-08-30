<?php

namespace App\Enums;

enum HolidayType: string
{
    case Regular = 'regular';
    case SpecialNonWorking = 'special_non_working';
    case SpecialWorking = 'special_working';
}
