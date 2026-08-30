<?php

namespace App\Policies;

use App\Enums\DefaultRole;
use App\Models\User;

/**
 * Superadmin bypasses all of this via Gate::before() (AppServiceProvider) —
 * these methods only run for everyone else, so they don't need to special-
 * case "actor is Superadmin". They do need to special-case the *target*
 * being a protected account: blueprint §30 requires that to hold even
 * against another Superadmin, which Gate::before() would otherwise wave
 * through before ever reaching here.
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('users.view');
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->can('users.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('users.create');
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->can('users.update');
    }

    public function disable(User $actor, User $target): bool
    {
        if ($target->is($actor)) {
            return false;
        }

        if ($target->is_protected) {
            return false;
        }

        return $actor->can('users.disable');
    }

    public function enable(User $actor, User $target): bool
    {
        return $actor->can('users.disable');
    }

    public function forceLogout(User $actor, User $target): bool
    {
        return $actor->can('users.update');
    }

    public function resetPassword(User $actor, User $target): bool
    {
        return $actor->can('users.update');
    }

    public function assignRoles(User $actor, User $target): bool
    {
        return $actor->can('roles.assign');
    }

    /**
     * Whether $target may lose the Superadmin role specifically — checked
     * in addition to assignRoles() by the controller, since it depends on
     * which roles are actually being removed, not just who's asking.
     */
    public function removeSuperadminRole(User $actor, User $target): bool
    {
        if ($target->is_protected) {
            return false;
        }

        // Never let the system end up with zero Superadmins.
        return User::role(DefaultRole::Superadmin->value)->where('id', '!=', $target->id)->exists();
    }
}
