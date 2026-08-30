<?php

namespace Tests\Feature\Admin;

use App\Enums\DefaultRole;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserManagementTest extends TestCase
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
        $user->givePermissionTo(['users.view', 'users.create', 'users.update', 'users.disable', 'roles.assign']);

        return $user;
    }

    public function test_users_without_permission_get_403(): void
    {
        $plainUser = User::factory()->create();

        $this->actingAs($plainUser)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_permitted_user_can_list_users(): void
    {
        $admin = $this->userAdmin();

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
    }

    public function test_creating_a_user_assigns_roles_and_sends_a_password_setup_link(): void
    {
        Notification::fake();
        $admin = $this->userAdmin();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Hire',
            'email' => 'new-hire@example.test',
            'roles' => [DefaultRole::HrStaff->value],
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $newUser = User::where('email', 'new-hire@example.test')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue($newUser->hasRole(DefaultRole::HrStaff->value));
        Notification::assertSentTo($newUser, ResetPassword::class);
    }

    public function test_a_protected_account_cannot_be_disabled(): void
    {
        $admin = $this->userAdmin();
        $protected = User::factory()->create(['is_protected' => true]);

        $response = $this->actingAs($admin)->post(route('admin.users.disable', $protected));

        $response->assertForbidden();
        $this->assertNull($protected->fresh()->disabled_at);
    }

    public function test_a_user_cannot_disable_their_own_account(): void
    {
        $admin = $this->userAdmin();

        $response = $this->actingAs($admin)->post(route('admin.users.disable', $admin));

        $response->assertForbidden();
        $this->assertNull($admin->fresh()->disabled_at);
    }

    public function test_disabling_an_ordinary_user_actually_persists(): void
    {
        $admin = $this->userAdmin();
        $target = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.users.disable', $target))->assertRedirect();

        $this->assertNotNull($target->fresh()->disabled_at);
    }

    public function test_disabling_a_user_destroys_their_sessions(): void
    {
        $admin = $this->userAdmin();
        $target = User::factory()->create();
        DB::table('sessions')->insert([
            'id' => 'target-session', 'user_id' => $target->id, 'payload' => 'x', 'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($admin)->post(route('admin.users.disable', $target));

        $this->assertDatabaseMissing('sessions', ['id' => 'target-session']);
    }

    public function test_the_superadmin_role_cannot_be_removed_from_a_protected_account(): void
    {
        $admin = $this->userAdmin();
        $protected = User::factory()->create(['is_protected' => true]);
        $protected->assignRole(DefaultRole::Superadmin->value);

        $response = $this->actingAs($admin)->put(route('admin.users.roles.update', $protected), [
            'roles' => [],
        ]);

        $response->assertSessionHasErrors('roles');
        $this->assertTrue($protected->fresh()->hasRole(DefaultRole::Superadmin->value));
    }

    public function test_the_last_superadmin_cannot_lose_the_role_even_if_unprotected(): void
    {
        $admin = $this->userAdmin();
        $onlySuperadmin = User::factory()->create();
        $onlySuperadmin->assignRole(DefaultRole::Superadmin->value);

        $response = $this->actingAs($admin)->put(route('admin.users.roles.update', $onlySuperadmin), [
            'roles' => [],
        ]);

        $response->assertSessionHasErrors('roles');
        $this->assertTrue($onlySuperadmin->fresh()->hasRole(DefaultRole::Superadmin->value));
    }

    public function test_the_superadmin_role_can_be_removed_when_another_superadmin_remains(): void
    {
        $admin = $this->userAdmin();
        User::factory()->create()->assignRole(DefaultRole::Superadmin->value);
        $secondSuperadmin = User::factory()->create();
        $secondSuperadmin->assignRole(DefaultRole::Superadmin->value);

        $response = $this->actingAs($admin)->put(route('admin.users.roles.update', $secondSuperadmin), [
            'roles' => [],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertFalse($secondSuperadmin->fresh()->hasRole(DefaultRole::Superadmin->value));
    }

    public function test_superadmin_bypasses_permission_checks_without_explicit_grants(): void
    {
        // Both two_factor_secret and two_factor_confirmed_at set directly
        // so the mfa.superadmin gate (tested separately, see
        // Auth\SuperadminMfaTest) doesn't redirect this request before it
        // reaches the assertion below — hasEnabledTwoFactorAuthentication()
        // requires both, not just confirmed_at.
        $superadmin = User::factory()->create([
            'two_factor_secret' => encrypt('placeholder-secret'),
            'two_factor_confirmed_at' => now(),
        ]);
        $superadmin->assignRole(DefaultRole::Superadmin->value);

        // No users.* permissions explicitly granted — Gate::before() should
        // still let this through.
        $this->actingAs($superadmin)->get(route('admin.users.index'))->assertOk();
    }
}
