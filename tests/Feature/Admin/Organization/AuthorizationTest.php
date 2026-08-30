<?php

namespace Tests\Feature\Admin\Organization;

use App\Enums\DefaultRole;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_superadmin_bypasses_organization_permissions_without_explicit_grants(): void
    {
        // See UserManagementTest::test_superadmin_bypasses_permission_checks_without_explicit_grants
        // for why both two_factor fields are set directly here.
        $superadmin = User::factory()->create([
            'two_factor_secret' => encrypt('placeholder-secret'),
            'two_factor_confirmed_at' => now(),
        ]);
        $superadmin->assignRole(DefaultRole::Superadmin->value);

        $this->actingAs($superadmin)->get(route('admin.organization.companies.index'))->assertOk();
        $this->actingAs($superadmin)->get(route('admin.organization.companies.create'))->assertOk();
        $this->actingAs($superadmin)->get(route('admin.organization.positions.index'))->assertOk();
        $this->actingAs($superadmin)->get(route('admin.organization.job-levels.create'))->assertOk();
    }

    /**
     * Regression guard on RoleAndPermissionSeeder: as of Phase 5, the
     * seeded HR roles only carry organization.view (read access to the
     * hierarchy they work within) — nobody is seeded with
     * organization.manage, so only Superadmin can edit organization data
     * until an admin deliberately grants it via the Phase 4 Roles UI.
     */
    public function test_seeded_hr_roles_can_view_but_not_manage_organization(): void
    {
        $hrAdmin = User::factory()->create();
        $hrAdmin->assignRole('HR Administrator');

        $hrStaff = User::factory()->create();
        $hrStaff->assignRole('HR Staff');

        foreach ([$hrAdmin, $hrStaff] as $user) {
            $this->actingAs($user)->get(route('admin.organization.companies.index'))->assertOk();
            $this->actingAs($user)->get(route('admin.organization.companies.create'))->assertForbidden();
        }
    }
}
