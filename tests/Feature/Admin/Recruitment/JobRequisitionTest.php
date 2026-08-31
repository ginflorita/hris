<?php

namespace Tests\Feature\Admin\Recruitment;

use App\Enums\JobRequisitionStatus;
use App\Models\Company;
use App\Models\Department;
use App\Models\JobRequisition;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobRequisitionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function recruiter(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['recruitment.view', 'recruitment.manage']);

        return $user;
    }

    public function test_submitting_a_requisition_starts_as_pending(): void
    {
        $user = $this->recruiter();
        $company = Company::factory()->create();
        $department = Department::factory()->for($company, 'company')->create();

        $this->actingAs($user)->post(route('admin.recruitment.requisitions.store'), [
            'company_id' => $company->id,
            'department_id' => $department->id,
            'openings_count' => 2,
            'justification' => 'Team is understaffed.',
        ])->assertRedirect();

        $requisition = JobRequisition::sole();
        $this->assertSame(JobRequisitionStatus::Pending, $requisition->status);
        $this->assertSame(2, $requisition->openings_count);
        $this->assertSame($user->id, $requisition->requested_by);
    }

    public function test_department_must_belong_to_the_selected_company(): void
    {
        $user = $this->recruiter();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $wrongDepartment = Department::factory()->for($companyB, 'company')->create();

        $this->actingAs($user)->post(route('admin.recruitment.requisitions.store'), [
            'company_id' => $companyA->id,
            'department_id' => $wrongDepartment->id,
            'openings_count' => 1,
        ])->assertSessionHasErrors('department_id');
    }

    public function test_approving_and_rejecting_only_works_on_pending_requisitions(): void
    {
        $user = $this->recruiter();
        $requisition = JobRequisition::factory()->create();

        $this->actingAs($user)->put(route('admin.recruitment.requisitions.approve', $requisition))->assertRedirect();
        $requisition->refresh();
        $this->assertSame(JobRequisitionStatus::Approved, $requisition->status);
        $this->assertSame($user->id, $requisition->approved_by);

        $this->actingAs($user)->put(route('admin.recruitment.requisitions.approve', $requisition))->assertStatus(422);

        $pending = JobRequisition::factory()->create();
        $this->actingAs($user)->put(route('admin.recruitment.requisitions.reject', $pending), [])
            ->assertSessionHasErrors('rejection_reason');
        $this->actingAs($user)->put(route('admin.recruitment.requisitions.reject', $pending), [
            'rejection_reason' => 'Budget frozen this quarter.',
        ])->assertRedirect();
        $this->assertSame(JobRequisitionStatus::Rejected, $pending->refresh()->status);
    }

    public function test_requires_permission(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('admin.recruitment.requisitions.index'))->assertForbidden();
        $this->actingAs($plain)->post(route('admin.recruitment.requisitions.store'), [])->assertForbidden();
    }

    public function test_position_field_is_optional(): void
    {
        $user = $this->recruiter();
        $company = Company::factory()->create();

        $this->actingAs($user)->post(route('admin.recruitment.requisitions.store'), [
            'company_id' => $company->id,
            'openings_count' => 1,
        ])->assertRedirect();

        $requisition = JobRequisition::sole();
        $this->assertNull($requisition->department_id);
        $this->assertNull($requisition->position_id);
    }
}
