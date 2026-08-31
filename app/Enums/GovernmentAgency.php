<?php

namespace App\Enums;

/**
 * A fixed, small set (unlike rates, which genuinely change over time and
 * need versioning — see ContributionRateTable) — modeled as an enum
 * rather than its own CRUD'able lookup table.
 */
enum GovernmentAgency: string
{
    case SSS = 'sss';
    case PhilHealth = 'philhealth';
    case PagIBIG = 'pagibig';
    case BIR = 'bir';
}
