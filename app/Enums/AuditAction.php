<?php

namespace App\Enums;

enum AuditAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Disabled = 'disabled';
    case Enabled = 'enabled';
    case RoleAssigned = 'role_assigned';
    case PermissionsChanged = 'permissions_changed';
    case Finalized = 'finalized';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::Updated => 'Updated',
            self::Disabled => 'Disabled',
            self::Enabled => 'Enabled',
            self::RoleAssigned => 'Role Assigned',
            self::PermissionsChanged => 'Permissions Changed',
            self::Finalized => 'Finalized',
        };
    }
}
