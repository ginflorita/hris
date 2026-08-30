<?php

namespace App\Enums;

enum ScheduleType: string
{
    case Fixed = 'fixed';
    case Flexible = 'flexible';
    case Rotating = 'rotating';
}
