<?php

namespace Database\Seeders;

use App\Enums\DataScope;
use App\Enums\DefaultRole;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the permission catalog (blueprint §33) and the default roles
 * (§32). Most permissions here are *reserved* ahead of their module —
 * e.g. `payroll.finalize` exists as a row before Payroll (Phase 11)
 * does, the same way `employees.view` did before Phase 6. They aren't
 * enforced anywhere until that module's controllers add
 * `$this->authorize(...)` calls; adding a permission here doesn't by
 * itself protect anything.
 *
 * Superadmin is intentionally NOT given every permission explicitly —
 * see AppServiceProvider's Gate::before(), which bypasses all permission
 * checks for that role. Assigning every permission by hand would silently
 * stop covering new permissions added by later phases.
 */
class RoleAndPermissionSeeder extends Seeder
{
    /**
     * @var array<string, list<string>>
     */
    private const PERMISSIONS = [
        'users' => ['view', 'create', 'update', 'disable'],
        'roles' => ['view', 'create', 'update', 'delete', 'assign'],
        'employees' => ['view', 'create', 'update', 'archive', 'export'],
        'employees.salary' => ['view'],
        'organization' => ['view', 'manage'],
        'attendance' => ['view', 'manage', 'correct'],
        'leave' => ['view', 'create', 'approve', 'reject'],
        'payroll' => ['view', 'create', 'process', 'approve', 'finalize', 'lock', 'export'],
        'payslips' => ['view', 'download'],
        'recruitment' => ['view', 'manage'],
        'performance' => ['view', 'manage'],
        'training' => ['view', 'manage'],
        'benefits' => ['view', 'manage'],
        'reports' => ['view'],
        'audit-logs' => ['view'],
    ];

    /**
     * @var array<string, array{scope: DataScope, permissions: list<string>}>
     */
    private const ROLES = [
        'HR Administrator' => [
            'scope' => DataScope::Company,
            'permissions' => [
                'employees.view', 'employees.create', 'employees.update', 'employees.archive', 'employees.export',
                'organization.view',
                'leave.view', 'leave.approve', 'leave.reject',
                'benefits.view',
                'reports.view',
            ],
        ],
        'HR Staff' => [
            'scope' => DataScope::Company,
            'permissions' => [
                'employees.view', 'employees.create', 'employees.update',
                'organization.view',
                'leave.view',
                'benefits.view',
            ],
        ],
        'Payroll Administrator' => [
            'scope' => DataScope::Company,
            'permissions' => [
                'payroll.view', 'payroll.create', 'payroll.process', 'payroll.approve', 'payroll.finalize', 'payroll.lock', 'payroll.export',
                'payslips.view', 'payslips.download',
                'employees.salary.view',
                'reports.view',
            ],
        ],
        'Attendance Officer' => [
            'scope' => DataScope::Company,
            'permissions' => [
                'attendance.view', 'attendance.manage', 'attendance.correct',
                'leave.view',
            ],
        ],
        'Recruitment Officer' => [
            'scope' => DataScope::Company,
            'permissions' => ['recruitment.view', 'recruitment.manage'],
        ],
        'Training Officer' => [
            'scope' => DataScope::Company,
            'permissions' => ['training.view', 'training.manage'],
        ],
        'Performance Officer' => [
            'scope' => DataScope::Company,
            'permissions' => ['performance.view', 'performance.manage'],
        ],
        // Deliberately no employees.salary.view — blueprint §19.
        'Manager' => [
            'scope' => DataScope::Team,
            'permissions' => [
                'employees.view',
                'leave.view', 'leave.approve', 'leave.reject',
                'attendance.view',
                'performance.view',
            ],
        ],
        // Self-service; Own is enforced once the underlying Employee/
        // Payslip models exist (Phase 6/12), not by this seeder.
        'Employee' => [
            'scope' => DataScope::Own,
            'permissions' => [
                'leave.view', 'leave.create',
                'attendance.view',
                'payslips.view', 'payslips.download',
            ],
        ],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            foreach (self::PERMISSIONS as $module => $actions) {
                foreach ($actions as $action) {
                    Permission::findOrCreate("{$module}.{$action}");
                }
            }

            // syncPermissions() below resolves names against the
            // registrar's in-memory cache, which was already warmed
            // (empty) before this seeder created any — without this it
            // can't find permissions created a few lines above.
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            Role::findOrCreate(DefaultRole::Superadmin->value)->update(['data_scope' => DataScope::All]);

            foreach (self::ROLES as $roleName => $definition) {
                $role = Role::findOrCreate($roleName);
                $role->update(['data_scope' => $definition['scope']]);
                $role->syncPermissions($definition['permissions']);
            }
        });
    }
}
