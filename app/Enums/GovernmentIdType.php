<?php

namespace App\Enums;

/**
 * The fixed set of government-issued ID categories this HRIS tracks —
 * not a rate or bracket, so it doesn't fall under the "no hard-coded
 * government contribution rates" rule (CLAUDE.md, blueprint §39); that
 * rule is about payroll amounts, not ID category names.
 */
enum GovernmentIdType: string
{
    case SSS = 'sss';
    case TIN = 'tin';
    case PhilHealth = 'philhealth';
    case PagIBIG = 'pagibig';
    case Passport = 'passport';
    case DriversLicense = 'drivers_license';
    case Other = 'other';
}
