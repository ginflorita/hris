<?php

namespace Tests\Feature\Admin\Organization;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function manager(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['organization.view', 'organization.manage']);

        return $user;
    }

    public function test_location_crud_and_optional_branch_scoping(): void
    {
        $user = $this->manager();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $branch = Branch::factory()->for($companyA, 'company')->create();

        // Branch from a different company than the location is rejected.
        $this->actingAs($user)->post(route('admin.organization.locations.store'), [
            'company_id' => $companyB->id,
            'branch_id' => $branch->id,
            'name' => 'Mismatched Site',
            'code' => 'MMS',
        ])->assertSessionHasErrors('branch_id');

        $this->actingAs($user)->post(route('admin.organization.locations.store'), [
            'company_id' => $companyA->id,
            'branch_id' => $branch->id,
            'name' => 'HQ Site',
            'code' => 'HQS',
            'address' => '1 Main St',
        ])->assertRedirect(route('admin.organization.locations.index'));

        $location = Location::sole();
        $this->assertTrue($location->is_active);
        $this->assertSame($branch->id, $location->branch_id);

        $this->actingAs($user)->put(route('admin.organization.locations.update', $location), [
            'company_id' => $companyA->id,
            'name' => 'HQ Site',
            'code' => 'HQS',
        ])->assertRedirect(route('admin.organization.locations.index'));
        $this->assertFalse($location->fresh()->is_active);

        $this->actingAs($user)->delete(route('admin.organization.locations.destroy', $location))
            ->assertRedirect(route('admin.organization.locations.index'));
        $this->assertSoftDeleted($location);
    }

    public function test_company_cannot_be_deleted_while_it_has_a_location(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();
        Location::factory()->for($company, 'company')->create();

        $this->actingAs($user)->delete(route('admin.organization.companies.destroy', $company))
            ->assertSessionHasErrors('company');
        $this->assertNotNull($company->fresh());
    }

    public function test_without_permission_gets_403(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('admin.organization.locations.index'))->assertForbidden();
    }
}
