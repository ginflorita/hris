<?php

namespace App\Enums;

enum JobRequisitionStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Closed = 'closed';
}
