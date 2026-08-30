<?php

namespace App\Enums;

enum EmploymentType: string
{
    case Probationary = 'probationary';
    case Regular = 'regular';
    case Contractual = 'contractual';
    case ProjectBased = 'project_based';
    case PartTime = 'part_time';
    case Consultant = 'consultant';
}
