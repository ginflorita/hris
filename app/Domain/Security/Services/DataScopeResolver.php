<?php

namespace App\Domain\Security\Services;

use App\Enums\DataScope;
use App\Models\Employee;
use App\Models\User;

/**
 * Blueprint §34: a permission never by itself implies access to every
 * employee -- the role's data_scope is what narrows "employees.view" down
 * to "employees.view, within my scope." CLAUDE.md has documented this as
 * a real, acknowledged gap since Phase 4: the pieces (Organization's
 * company_id, Employee's company_id, Employment's manager_id/
 * department_id/branch_id) all exist now, but nothing queried
 * roles.data_scope and filtered by it.
 *
 * This resolver closes that gap for exactly the two scopes a seeded role
 * actually uses today: Own (the `Employee` self-service role -- though
 * portal controllers already hard-code employee_id ownership checks of
 * their own, so this mainly matters if an Own-scoped role is ever also
 * granted an admin-side permission) and Team (the `Manager` role,
 * resolved via Employment.manager_id -- see Employee::scopeReportingTo()).
 * Department/Branch/Company/All stay unenforced on purpose, the same restraint
 * CLAUDE.md applies elsewhere (LeaveBalanceService, AttendanceCorrection
 * Service): building general enforcement for scopes no seeded role
 * exercises would be speculative, and every existing Company-scoped role
 * (HR Administrator, HR Staff, Payroll Administrator, Attendance
 * Officer, ...) keeps exactly its current, already-shipped, already-
 * tested behavior -- employeeIdsFor() only ever *restricts* for a user
 * whose broadest role for that permission is Own or Team.
 */
class DataScopeResolver
{
    /**
     * @return list<int>|null null means unrestricted -- caller should not filter.
     */
    public function employeeIdsFor(User $user, string $permission): ?array
    {
        if ($user->isSuperadmin()) {
            return null;
        }

        $scopes = $user->roles
            ->filter(fn ($role) => $role->hasPermissionTo($permission))
            ->pluck('data_scope');

        // The permission is already guaranteed held (every call site sits
        // behind $this->authorize($permission)); an empty $scopes means it
        // was granted directly to the user rather than through any Role,
        // so there's no data_scope to consult -- unrestricted, not "owns
        // nothing". data_scope only ever narrows a *role's* reach.
        if ($scopes->isEmpty()) {
            return null;
        }

        if ($scopes->contains(fn (?DataScope $scope) => ! in_array($scope, [DataScope::Own, DataScope::Team], true))) {
            return null;
        }

        $employee = $user->employee;

        if (! $employee) {
            return [];
        }

        if ($scopes->contains(DataScope::Team)) {
            return Employee::reportingTo($employee->id)->pluck('id')->all();
        }

        return [$employee->id];
    }
}
