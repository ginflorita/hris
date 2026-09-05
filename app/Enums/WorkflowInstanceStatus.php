<?php

namespace App\Enums;

enum WorkflowInstanceStatus: string
{
    case InProgress = 'in_progress';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function isTerminal(): bool
    {
        return $this !== self::InProgress;
    }
}
