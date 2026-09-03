<?php

namespace Tests\Feature\Security;

use App\Enums\AuditAction;
use App\Enums\DefaultRole;
use App\Enums\PayrollPeriodStatus;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function userAdmin(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['users.view', 'users.create', 'users.update', 'users.disable', 'roles.assign', 'roles.create', 'roles.update']);

        return $user;
    }

    private function auditViewer(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['audit-logs.view']);

        return $user;
    }

    public function test_creating_a_user_writes_an_audit_log_entry(): void
    {
        Notification::fake();
        $admin = $this->userAdmin();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Hire',
            'email' => 'audit-new-hire@example.test',
            'roles' => [DefaultRole::HrStaff->value],
        ])->assertRedirect();

        $newUser = User::where('email', 'audit-new-hire@example.test')->first();
        $log = AuditLog::where('auditable_type', User::class)->where('auditable_id', $newUser->id)->sole();

        $this->assertSame(AuditAction::Created, $log->action);
        $this->assertSame('User Management', $log->module);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame(DefaultRole::HrStaff->value, $log->after['roles']);
    }

    public function test_assigning_roles_writes_a_before_and_after_entry(): void
    {
        $admin = $this->userAdmin();
        $target = User::factory()->create();
        $target->assignRole(DefaultRole::HrStaff->value);

        $this->actingAs($admin)->put(route('admin.users.roles.update', $target), [
            'roles' => [DefaultRole::Manager->value],
        ])->assertRedirect();

        $log = AuditLog::where('action', AuditAction::RoleAssigned)->where('auditable_id', $target->id)->sole();

        $this->assertSame(DefaultRole::HrStaff->value, $log->before['roles']);
        $this->assertSame(DefaultRole::Manager->value, $log->after['roles']);
    }

    public function test_disabling_and_enabling_a_user_writes_audit_entries(): void
    {
        $admin = $this->userAdmin();
        $target = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.users.disable', $target))->assertRedirect();
        $this->assertSame(
            AuditAction::Disabled,
            AuditLog::where('auditable_id', $target->id)->latest('id')->first()->action,
        );

        $this->actingAs($admin)->post(route('admin.users.enable', $target))->assertRedirect();
        $this->assertSame(
            AuditAction::Enabled,
            AuditLog::where('auditable_id', $target->id)->latest('id')->first()->action,
        );
    }

    public function test_creating_and_updating_a_role_writes_audit_entries(): void
    {
        $admin = $this->userAdmin();

        $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'Audit Test Role',
            'data_scope' => 'company',
            'permissions' => ['employees.view'],
        ])->assertRedirect();

        $role = Role::where('name', 'Audit Test Role')->sole();
        $createLog = AuditLog::where('auditable_type', Role::class)->where('auditable_id', $role->id)->sole();
        $this->assertSame(AuditAction::Created, $createLog->action);

        $this->actingAs($admin)->put(route('admin.roles.update', $role), [
            'name' => 'Audit Test Role',
            'data_scope' => 'company',
            'permissions' => ['employees.view', 'employees.update'],
        ])->assertRedirect();

        $updateLog = AuditLog::where('action', AuditAction::PermissionsChanged)->where('auditable_id', $role->id)->sole();
        $this->assertStringContainsString('employees.view', $updateLog->before['permissions']);
        $this->assertStringNotContainsString('employees.update', $updateLog->before['permissions']);
        $this->assertStringContainsString('employees.update', $updateLog->after['permissions']);
    }

    public function test_a_salary_change_on_employment_writes_an_audit_entry(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(['employees.view', 'employees.update']);
        $company = Company::factory()->create();
        $employee = Employee::factory()->for($company, 'company')->create();

        $this->actingAs($admin)->post(route('admin.employees.employments.store', $employee), [
            'employment_type' => 'probationary',
            'status' => 'active',
            'change_type' => 'hire',
            'effective_date' => '2026-01-01',
            'basic_salary' => 25000,
        ])->assertRedirect();

        $hireLog = AuditLog::where('module', 'Employee Compensation')->where('auditable_id', $employee->id)->sole();
        $this->assertSame('(none)', $hireLog->before['basic_salary']);
        $this->assertSame('25,000.00', $hireLog->after['basic_salary']);

        $this->actingAs($admin)->post(route('admin.employees.employments.store', $employee), [
            'employment_type' => 'regular',
            'status' => 'active',
            'change_type' => 'salary_change',
            'effective_date' => '2026-02-01',
            'basic_salary' => 30000,
        ])->assertRedirect();

        $raiseLog = AuditLog::where('module', 'Employee Compensation')->where('auditable_id', $employee->id)->latest('id')->first();
        $this->assertSame('25,000.00', $raiseLog->before['basic_salary']);
        $this->assertSame('30,000.00', $raiseLog->after['basic_salary']);
    }

    public function test_an_employment_change_without_a_salary_change_writes_no_audit_entry(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(['employees.view', 'employees.update']);
        $company = Company::factory()->create();
        $employee = Employee::factory()->for($company, 'company')->create();

        $this->actingAs($admin)->post(route('admin.employees.employments.store', $employee), [
            'employment_type' => 'probationary',
            'status' => 'active',
            'change_type' => 'hire',
            'effective_date' => '2026-01-01',
            'basic_salary' => 25000,
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.employees.employments.store', $employee), [
            'employment_type' => 'regular',
            'status' => 'active',
            'change_type' => 'regularization',
            'effective_date' => '2026-04-01',
            'basic_salary' => 25000,
        ])->assertRedirect();

        $this->assertSame(1, AuditLog::where('module', 'Employee Compensation')->where('auditable_id', $employee->id)->count());
    }

    public function test_finalizing_a_payroll_period_writes_an_audit_entry(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(['payroll.view', 'payroll.finalize']);
        $period = PayrollPeriod::factory()->create(['status' => PayrollPeriodStatus::Approved]);

        $this->actingAs($admin)->post(route('admin.payroll.payroll-periods.finalize', $period))->assertRedirect();

        $log = AuditLog::where('action', AuditAction::Finalized)->where('auditable_id', $period->id)->sole();
        $this->assertSame('Payroll', $log->module);
        $this->assertSame('approved', $log->before['status']);
        $this->assertSame('finalized', $log->after['status']);
    }

    public function test_audit_log_index_requires_permission(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('admin.audit-logs.index'))->assertForbidden();
    }

    public function test_audit_log_index_lists_entries_for_a_permitted_viewer(): void
    {
        $viewer = $this->auditViewer();
        AuditLog::factory()->create(['module' => 'User Management']);

        $this->actingAs($viewer)->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertSee('User Management');
    }

    public function test_superadmin_can_view_audit_logs_without_an_explicit_grant(): void
    {
        // Both two_factor_secret and two_factor_confirmed_at set directly so
        // the mfa.superadmin gate doesn't redirect this request first — see
        // UserManagementTest::test_superadmin_bypasses_permission_checks_without_explicit_grants().
        $superadmin = User::factory()->create([
            'two_factor_secret' => encrypt('placeholder-secret'),
            'two_factor_confirmed_at' => now(),
        ]);
        $superadmin->assignRole(DefaultRole::Superadmin->value);

        $this->actingAs($superadmin)->get(route('admin.audit-logs.index'))->assertOk();
    }
}
