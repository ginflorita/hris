<?php

namespace App\Enums;

enum LeaveTransactionType: string
{
    case Accrual = 'accrual';
    case Usage = 'usage';
    case Adjustment = 'adjustment';
    case CarryOver = 'carry_over';
    case Reversal = 'reversal';
}
