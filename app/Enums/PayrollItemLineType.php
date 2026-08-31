<?php

namespace App\Enums;

enum PayrollItemLineType: string
{
    case Earning = 'earning';
    case Deduction = 'deduction';
}
