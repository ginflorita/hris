<?php

namespace App\Enums;

enum AddressType: string
{
    case Current = 'current';
    case Permanent = 'permanent';
    case Provincial = 'provincial';
}
