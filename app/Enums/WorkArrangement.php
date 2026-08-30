<?php

namespace App\Enums;

enum WorkArrangement: string
{
    case Onsite = 'onsite';
    case Remote = 'remote';
    case Hybrid = 'hybrid';
}
