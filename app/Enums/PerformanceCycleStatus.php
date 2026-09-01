<?php

namespace App\Enums;

enum PerformanceCycleStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Closed = 'closed';
}
