<?php

namespace Tests\Feature\Admin\Organization;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
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

    public function test_manager_can_create_a_company_with_is_active_defaulting_true(): void
    {
        $user = $this->manager();

        $this->actingAs($user)->post(route('admin.organization.companies.store'), [
            'name' => 'Acme Corp',
            'code' => 'ACME',
        ])->assertRedirect(route('admin.organization.companies.index'));

        $company = Company::sole();
        $this->assertSame('Acme Corp', $company->name);
        $this->assertTrue($company->is_active);
    }

    public function test_company_code_must_be_unique(): void
    {
        $user = $this->manager();
        Company::factory()->create(['code' => 'DUP']);

        $this->actingAs($user)->post(route('admin.organization.companies.store'), [
            'name' => 'Another Co',
            'code' => 'DUP',
        ])->assertSessionHasErrors('code');
        $this->assertSame(1, Company::count());
    }

    public function test_unchecking_is_active_on_update_actually_persists(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create(['is_active' => true]);

        $this->actingAs($user)->put(route('admin.organization.companies.update', $company), [
            'name' => $company->name,
            'code' => $company->code,
            // is_active omitted -> unchecked
        ])->assertRedirect(route('admin.organization.companies.index'));

        $this->assertFalse($company->fresh()->is_active);
    }

    public function test_company_cannot_be_deleted_while_it_has_a_branch(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();
        Branch::factory()->for($company, 'company')->create();

        $this->actingAs($user)->delete(route('admin.organization.companies.destroy', $company))
            ->assertSessionHasErrors('company');
        $this->assertNotNull($company->fresh());
    }

    public function test_company_with_no_children_can_be_deleted(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();

        $this->actingAs($user)->delete(route('admin.organization.companies.destroy', $company))
            ->assertRedirect(route('admin.organization.companies.index'));
        $this->assertSoftDeleted($company);
    }

    public function test_view_only_permission_allows_index_but_not_create(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('organization.view');

        $this->actingAs($user)->get(route('admin.organization.companies.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.organization.companies.create'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.organization.companies.store'), [
            'name' => 'Nope', 'code' => 'NOPE',
        ])->assertForbidden();
    }

    public function test_user_without_any_permission_gets_403(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('admin.organization.companies.index'))->assertForbidden();
    }
}
