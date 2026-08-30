<?php

namespace Tests\Feature\Admin;

use App\Enums\DataScope;
use App\Enums\DefaultRole;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function roleAdmin(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['roles.view', 'roles.create', 'roles.update', 'roles.delete']);

        return $user;
    }

    public function test_users_without_permission_get_403(): void
    {
        $plainUser = User::factory()->create();

        $this->actingAs($plainUser)->get(route('admin.roles.index'))->assertForbidden();
    }

    public function test_a_role_can_be_created_with_permissions_and_a_data_scope(): void
    {
        $admin = $this->roleAdmin();

        $response = $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'Branch Auditor',
            'data_scope' => DataScope::Branch->value,
            'permissions' => ['attendance.view'],
        ]);

        $response->assertRedirect(route('admin.roles.index'));
        $role = Role::where('name', 'Branch Auditor')->first();
        $this->assertNotNull($role);
        $this->assertSame(DataScope::Branch, $role->data_scope);
        $this->assertTrue($role->hasPermissionTo('attendance.view'));
    }

    public function test_a_role_can_be_updated(): void
    {
        $admin = $this->roleAdmin();
        $role = Role::findOrCreate('Temp Role');

        $response = $this->actingAs($admin)->put(route('admin.roles.update', $role), [
            'name' => 'Temp Role Renamed',
            'data_scope' => DataScope::Department->value,
            'permissions' => ['leave.view'],
        ]);

        $response->assertRedirect(route('admin.roles.index'));
        $role->refresh();
        $this->assertSame('Temp Role Renamed', $role->name);
        $this->assertSame(DataScope::Department, $role->data_scope);
        $this->assertTrue($role->hasPermissionTo('leave.view'));
    }

    public function test_the_superadmin_role_cannot_be_edited(): void
    {
        $admin = $this->roleAdmin();
        $superadminRole = Role::where('name', DefaultRole::Superadmin->value)->first();

        $this->actingAs($admin)->get(route('admin.roles.edit', $superadminRole))->assertForbidden();

        $response = $this->actingAs($admin)->put(route('admin.roles.update', $superadminRole), [
            'name' => 'Hacked',
            'data_scope' => DataScope::Own->value,
            'permissions' => [],
        ]);
        $response->assertForbidden();
        $this->assertSame(DefaultRole::Superadmin->value, $superadminRole->fresh()->name);
    }

    public function test_the_superadmin_role_cannot_be_deleted(): void
    {
        $admin = $this->roleAdmin();
        $superadminRole = Role::where('name', DefaultRole::Superadmin->value)->first();

        $response = $this->actingAs($admin)->delete(route('admin.roles.destroy', $superadminRole));

        $response->assertForbidden();
        $this->assertNotNull(Role::find($superadminRole->id));
    }

    public function test_a_role_with_members_cannot_be_deleted(): void
    {
        $admin = $this->roleAdmin();
        $hrAdminRole = Role::where('name', DefaultRole::HrAdministrator->value)->first();
        User::factory()->create()->assignRole($hrAdminRole->name);

        $response = $this->actingAs($admin)->delete(route('admin.roles.destroy', $hrAdminRole));

        $response->assertSessionHasErrors('role');
        $this->assertNotNull(Role::find($hrAdminRole->id));
    }

    public function test_an_empty_role_can_be_deleted(): void
    {
        $admin = $this->roleAdmin();
        $role = Role::findOrCreate('Disposable Role');

        $response = $this->actingAs($admin)->delete(route('admin.roles.destroy', $role));

        $response->assertRedirect(route('admin.roles.index'));
        $this->assertNull(Role::find($role->id));
    }

    public function test_permissions_index_is_visible_to_role_viewers(): void
    {
        $admin = $this->roleAdmin();

        $this->actingAs($admin)->get(route('admin.permissions.index'))
            ->assertOk()
            ->assertSee('employees.view');
    }
}
