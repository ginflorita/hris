<?php

namespace App\Enums;

enum ContactType: string
{
    case Mobile = 'mobile';
    case Landline = 'landline';
    case Email = 'email';
    case Social = 'social';
    case Other = 'other';
}
