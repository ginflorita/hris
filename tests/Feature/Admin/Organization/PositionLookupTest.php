<?php

namespace Tests\Feature\Admin\Organization;

use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\JobGrade;
use App\Models\JobLevel;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers Position and the three lookup tables it draws from: JobLevel,
 * JobGrade, CostCenter. All four are company-scoped like the rest of the
 * hierarchy, and their admin routes use Laravel's dash-to-underscore
 * route parameter naming (job-levels -> {job_level}) rather than the
 * plain singular of the URI segment, which is worth pinning down with a
 * real request rather than trusting implicit binding to "just work".
 */
class PositionLookupTest extends TestCase
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

    public function test_job_level_crud_with_kebab_route_binding(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();

        $this->actingAs($user)->post(route('admin.organization.job-levels.store'), [
            'company_id' => $company->id,
            'name' => 'Senior',
            'code' => 'L5',
            'rank' => 5,
        ])->assertRedirect(route('admin.organization.job-levels.index'));

        $jobLevel = JobLevel::sole();
        $this->assertTrue($jobLevel->is_active);

        $this->actingAs($user)->put(route('admin.organization.job-levels.update', $jobLevel), [
            'company_id' => $company->id,
            'name' => 'Senior II',
            'code' => 'L5',
            'rank' => 5,
        ])->assertRedirect(route('admin.organization.job-levels.index'));
        $this->assertSame('Senior II', $jobLevel->fresh()->name);
    }

    public function test_job_grade_crud_with_kebab_route_binding(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();

        $this->actingAs($user)->post(route('admin.organization.job-grades.store'), [
            'company_id' => $company->id,
            'name' => 'Grade A',
            'code' => 'GA',
            'rank' => 1,
        ])->assertRedirect(route('admin.organization.job-grades.index'));

        $jobGrade = JobGrade::sole();

        $this->actingAs($user)->delete(route('admin.organization.job-grades.destroy', $jobGrade))
            ->assertRedirect(route('admin.organization.job-grades.index'));
        $this->assertSoftDeleted($jobGrade);
    }

    public function test_rank_and_code_are_required_and_code_scoped_per_company(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();
        JobLevel::factory()->for($company, 'company')->create(['code' => 'DUP']);

        $this->actingAs($user)->post(route('admin.organization.job-levels.store'), [
            'company_id' => $company->id,
            'name' => 'Another',
            'code' => 'DUP',
            'rank' => 2,
        ])->assertSessionHasErrors('code');

        $this->actingAs($user)->post(route('admin.organization.job-levels.store'), [
            'company_id' => $company->id,
            'name' => 'Missing rank',
            'code' => 'NEW',
        ])->assertSessionHasErrors('rank');
    }

    public function test_cost_center_department_must_belong_to_same_company(): void
    {
        $user = $this->manager();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $department = Department::factory()->for($companyA, 'company')->create();

        $this->actingAs($user)->post(route('admin.organization.cost-centers.store'), [
            'company_id' => $companyB->id,
            'department_id' => $department->id,
            'name' => 'Mismatched',
            'code' => 'CCX',
        ])->assertSessionHasErrors('department_id');

        $this->actingAs($user)->post(route('admin.organization.cost-centers.store'), [
            'company_id' => $companyA->id,
            'department_id' => $department->id,
            'name' => 'Finance CC',
            'code' => 'CCFIN',
        ])->assertRedirect(route('admin.organization.cost-centers.index'));

        $costCenter = CostCenter::sole();
        $this->assertSame($department->id, $costCenter->department_id);

        $this->actingAs($user)->delete(route('admin.organization.cost-centers.destroy', $costCenter))
            ->assertRedirect(route('admin.organization.cost-centers.index'));
        $this->assertSoftDeleted($costCenter);
    }

    public function test_position_requires_job_level_and_job_grade_from_the_same_company(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $jobLevel = JobLevel::factory()->for($company, 'company')->create();
        $jobGrade = JobGrade::factory()->for($company, 'company')->create();

        $this->actingAs($user)->post(route('admin.organization.positions.store'), [
            'company_id' => $otherCompany->id,
            'job_level_id' => $jobLevel->id,
            'job_grade_id' => $jobGrade->id,
            'title' => 'Engineer',
            'code' => 'ENG1',
        ])->assertSessionHasErrors(['job_level_id', 'job_grade_id']);

        $this->actingAs($user)->post(route('admin.organization.positions.store'), [
            'company_id' => $company->id,
            'job_level_id' => $jobLevel->id,
            'job_grade_id' => $jobGrade->id,
            'title' => 'Engineer',
            'code' => 'ENG1',
            'description' => 'Builds things.',
        ])->assertRedirect(route('admin.organization.positions.index'));

        $position = Position::sole();
        $this->assertSame($jobLevel->id, $position->job_level_id);
        $this->assertSame($jobGrade->id, $position->job_grade_id);
        $this->assertTrue($position->is_active);
    }

    public function test_job_level_and_job_grade_cannot_be_deleted_while_a_position_references_them(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();
        $jobLevel = JobLevel::factory()->for($company, 'company')->create();
        $jobGrade = JobGrade::factory()->for($company, 'company')->create();
        $position = Position::factory()->for($company, 'company')->create([
            'job_level_id' => $jobLevel->id,
            'job_grade_id' => $jobGrade->id,
        ]);

        $this->actingAs($user)->delete(route('admin.organization.job-levels.destroy', $jobLevel))
            ->assertSessionHasErrors('jobLevel');
        $this->actingAs($user)->delete(route('admin.organization.job-grades.destroy', $jobGrade))
            ->assertSessionHasErrors('jobGrade');

        $position->delete();

        $this->actingAs($user)->delete(route('admin.organization.job-levels.destroy', $jobLevel))
            ->assertRedirect(route('admin.organization.job-levels.index'));
        $this->actingAs($user)->delete(route('admin.organization.job-grades.destroy', $jobGrade))
            ->assertRedirect(route('admin.organization.job-grades.index'));

        $this->assertSoftDeleted($jobLevel);
        $this->assertSoftDeleted($jobGrade);
    }

    public function test_position_can_be_deleted_with_no_blocking_since_it_is_a_leaf(): void
    {
        $user = $this->manager();
        $position = Position::factory()->create();

        $this->actingAs($user)->delete(route('admin.organization.positions.destroy', $position))
            ->assertRedirect(route('admin.organization.positions.index'));
        $this->assertSoftDeleted($position);
    }

    public function test_without_permission_gets_403(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('admin.organization.job-levels.index'))->assertForbidden();
        $this->actingAs($plain)->get(route('admin.organization.job-grades.index'))->assertForbidden();
        $this->actingAs($plain)->get(route('admin.organization.cost-centers.index'))->assertForbidden();
        $this->actingAs($plain)->get(route('admin.organization.positions.index'))->assertForbidden();
    }
}
