<?php

namespace App\Enums;

enum TrainingEnrollmentStatus: string
{
    case Enrolled = 'enrolled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';
}
