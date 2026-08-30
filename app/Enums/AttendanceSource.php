<?php

namespace App\Enums;

enum AttendanceSource: string
{
    case Web = 'web';
    case Mobile = 'mobile';
    case Biometric = 'biometric';
    case Rfid = 'rfid';
    case Import = 'import';
    case Api = 'api';
    case Manual = 'manual';
}
