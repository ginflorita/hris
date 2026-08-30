<?php

namespace Tests\Feature\Admin\Organization;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use App\Models\Section;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers Branch, Division, Department, Section, Team: the company-scoped
 * hierarchy beneath Company. Each level's create/edit forms take an
 * optional immediate-parent id that must belong to the same company as
 * the record itself — that cross-company validation, not just plain
 * CRUD, is the main thing worth regression-testing here (§34 data scope
 * ultimately depends on this hierarchy being trustworthy).
 */
class OrgHierarchyTest extends TestCase
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

    public function test_branch_crud_and_address_fields(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();

        $this->actingAs($user)->post(route('admin.organization.branches.store'), [
            'company_id' => $company->id,
            'name' => 'Main Branch',
            'code' => 'MB',
            'address' => '123 Main St',
            'phone' => '555-1234',
        ])->assertRedirect(route('admin.organization.branches.index'));

        $branch = Branch::sole();
        $this->assertTrue($branch->is_active);
        $this->assertSame('123 Main St', $branch->address);

        $this->actingAs($user)->put(route('admin.organization.branches.update', $branch), [
            'company_id' => $company->id,
            'name' => 'Main Branch',
            'code' => 'MB',
            // is_active omitted -> unchecked
        ])->assertRedirect(route('admin.organization.branches.index'));
        $this->assertFalse($branch->fresh()->is_active);

        $this->actingAs($user)->delete(route('admin.organization.branches.destroy', $branch))
            ->assertRedirect(route('admin.organization.branches.index'));
        $this->assertSoftDeleted($branch);
    }

    public function test_division_department_section_team_crud_chain(): void
    {
        $user = $this->manager();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $this->actingAs($user)->post(route('admin.organization.divisions.store'), [
            'company_id' => $companyA->id,
            'name' => 'Ops Division',
            'code' => 'OPSD',
        ])->assertRedirect(route('admin.organization.divisions.index'));

        $division = Division::sole();
        $this->assertTrue($division->is_active);
        $this->assertSame($companyA->id, $division->company_id);

        // Department scoped to a division from a DIFFERENT company is rejected.
        $this->actingAs($user)->post(route('admin.organization.departments.store'), [
            'company_id' => $companyB->id,
            'division_id' => $division->id,
            'name' => 'Mismatched Dept',
            'code' => 'MMD',
        ])->assertSessionHasErrors('division_id');
        $this->assertSame(0, Department::count());

        $this->actingAs($user)->post(route('admin.organization.departments.store'), [
            'company_id' => $companyA->id,
            'division_id' => $division->id,
            'name' => 'Engineering',
            'code' => 'ENG',
        ])->assertRedirect(route('admin.organization.departments.index'));

        $department = Department::sole();
        $this->assertTrue($department->is_active);

        $this->actingAs($user)->put(route('admin.organization.departments.update', $department), [
            'company_id' => $companyA->id,
            'division_id' => $division->id,
            'name' => 'Engineering',
            'code' => 'ENG',
        ])->assertRedirect(route('admin.organization.departments.index'));
        $this->assertFalse($department->fresh()->is_active);

        $this->actingAs($user)->delete(route('admin.organization.divisions.destroy', $division))
            ->assertSessionHasErrors('division');
        $this->assertNotNull($division->fresh());

        $this->actingAs($user)->post(route('admin.organization.sections.store'), [
            'company_id' => $companyA->id,
            'department_id' => $department->id,
            'name' => 'Backend',
            'code' => 'BE',
        ])->assertRedirect(route('admin.organization.sections.index'));

        $section = Section::sole();

        $this->actingAs($user)->delete(route('admin.organization.departments.destroy', $department))
            ->assertSessionHasErrors('department');

        $this->actingAs($user)->post(route('admin.organization.teams.store'), [
            'company_id' => $companyB->id,
            'section_id' => $section->id,
            'name' => 'Platform',
            'code' => 'PLAT',
        ])->assertSessionHasErrors('section_id');

        $this->actingAs($user)->post(route('admin.organization.teams.store'), [
            'company_id' => $companyA->id,
            'department_id' => $department->id,
            'section_id' => $section->id,
            'name' => 'Platform',
            'code' => 'PLAT',
        ])->assertRedirect(route('admin.organization.teams.index'));

        $team = Team::sole();
        $this->assertSame($section->id, $team->section_id);

        $this->actingAs($user)->delete(route('admin.organization.sections.destroy', $section))
            ->assertSessionHasErrors('section');

        $this->actingAs($user)->delete(route('admin.organization.teams.destroy', $team))
            ->assertRedirect(route('admin.organization.teams.index'));
        $this->assertSoftDeleted($team);

        $this->actingAs($user)->delete(route('admin.organization.sections.destroy', $section))
            ->assertRedirect(route('admin.organization.sections.index'));
        $this->actingAs($user)->delete(route('admin.organization.departments.destroy', $department))
            ->assertRedirect(route('admin.organization.departments.index'));
        $this->actingAs($user)->delete(route('admin.organization.divisions.destroy', $division))
            ->assertRedirect(route('admin.organization.divisions.index'));

        $this->assertSoftDeleted($section);
        $this->assertSoftDeleted($department);
        $this->assertSoftDeleted($division);
    }

    public function test_code_uniqueness_is_scoped_per_company_not_global(): void
    {
        $user = $this->manager();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        Division::factory()->for($companyA, 'company')->create(['code' => 'DUP']);

        $this->actingAs($user)->post(route('admin.organization.divisions.store'), [
            'company_id' => $companyB->id,
            'name' => 'Reused Code Division',
            'code' => 'DUP',
        ])->assertRedirect(route('admin.organization.divisions.index'));
        $this->assertSame(2, Division::count());

        $this->actingAs($user)->post(route('admin.organization.divisions.store'), [
            'company_id' => $companyA->id,
            'name' => 'Another Division',
            'code' => 'DUP',
        ])->assertSessionHasErrors('code');
        $this->assertSame(2, Division::count());
    }

    public function test_without_permission_gets_403_on_every_level(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('admin.organization.branches.index'))->assertForbidden();
        $this->actingAs($plain)->get(route('admin.organization.divisions.index'))->assertForbidden();
        $this->actingAs($plain)->get(route('admin.organization.departments.index'))->assertForbidden();
        $this->actingAs($plain)->get(route('admin.organization.sections.index'))->assertForbidden();
        $this->actingAs($plain)->get(route('admin.organization.teams.index'))->assertForbidden();
    }
}
