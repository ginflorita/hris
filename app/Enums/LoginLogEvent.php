<?php

namespace App\Enums;

enum LoginLogEvent: string
{
    case Login = 'login';
    case FailedLogin = 'failed_login';
    case Logout = 'logout';
}
