<?php

namespace App\Enums;

/**
 * Why a new employments row was inserted — every row is one of these,
 * chosen by the person recording the change. Purely descriptive (drives
 * the Employment History timeline label); it doesn't change validation.
 */
enum EmploymentChangeType: string
{
    case Hire = 'hire';
    case Promotion = 'promotion';
    case Transfer = 'transfer';
    case SalaryChange = 'salary_change';
    case Regularization = 'regularization';
    case Separation = 'separation';
    case Other = 'other';
}
