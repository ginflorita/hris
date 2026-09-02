<?php

namespace App\Enums;

enum PerformanceReviewType: string
{
    case Self = 'self';
    case Manager = 'manager';
    case Peer = 'peer';
}
