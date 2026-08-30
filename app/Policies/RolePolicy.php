<?php

namespace App\Policies;

use App\Enums\DefaultRole;
use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('roles.view');
    }

    public function view(User $actor, Role $role): bool
    {
        return $actor->can('roles.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('roles.create');
    }

    /**
     * The Superadmin role's permission list is edited nowhere in the UI —
     * its real power is Gate::before() (AppServiceProvider), not this
     * list, so letting anyone edit it would just be misleading rather
     * than unsafe.
     */
    public function update(User $actor, Role $role): bool
    {
        if ($role->name === DefaultRole::Superadmin->value) {
            return false;
        }

        return $actor->can('roles.update');
    }

    public function delete(User $actor, Role $role): bool
    {
        if ($role->name === DefaultRole::Superadmin->value) {
            return false;
        }

        return $actor->can('roles.delete');
    }
}
