<?php

namespace App\Enums;

/**
 * How far a role's permissions reach, independent of *which* permissions
 * it has (blueprint §29 "User → Role → Permission → Module → Action →
 * Data Scope", §34). `employees.view` means something different for a
 * role scoped to Own vs. one scoped to All.
 *
 * One scope per role (roles.data_scope), not per permission grant — in
 * practice a role's permissions share one reach (a department manager
 * doesn't see attendance company-wide but leave requests department-only),
 * and one column per role is far simpler to reason about and query than a
 * scope on every row of role_has_permissions. Revisit only if a concrete
 * role genuinely needs mixed scopes across its own permissions.
 *
 * Ordered loosest-contained to broadest; cases beyond Own/All are inert
 * until Phase 5 (Organization) and Phase 6 (Employee) exist to scope
 * against — see CLAUDE.md "Data scope" for how a Domain model should
 * consume this once it does.
 */
enum DataScope: string
{
    case Own = 'own';
    case Team = 'team';
    case Department = 'department';
    case Branch = 'branch';
    case Company = 'company';
    case All = 'all';
}
