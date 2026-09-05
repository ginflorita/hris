<?php

namespace App\Enums;

enum WorkflowApproverType: string
{
    case Manager = 'manager';
    case Permission = 'permission';
}
