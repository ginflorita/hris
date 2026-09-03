<?php

namespace Tests\Feature\Admin\Reports;

use App\Enums\ApplicationStatus;
use App\Enums\JobRequisitionStatus;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\JobRequisition;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruitmentReportTest extends TestCase
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
        $user->givePermissionTo('recruitment.view');

        return $user;
    }

    public function test_recruitment_report_requires_recruitment_view_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('reports.view');

        $this->actingAs($user)->get(route('admin.reports.recruitment.index'))->assertForbidden();
        $this->actingAs($this->recruiter())->get(route('admin.reports.recruitment.index'))->assertOk();
    }

    public function test_recruitment_report_counts_the_application_pipeline_and_requisitions(): void
    {
        $company = Company::factory()->create();
        $requisition = JobRequisition::factory()->for($company, 'company')->approved()->create();
        $posting = JobPosting::factory()->forRequisition($requisition)->published()->create();

        Application::factory()->between(Applicant::factory()->create(), $posting)->create(['status' => 'applied']);
        Application::factory()->between(Applicant::factory()->create(), $posting)->create(['status' => 'screening']);
        Application::factory()->between(Applicant::factory()->create(), $posting)->create(['status' => 'hired']);

        JobRequisition::factory()->for($company, 'company')->create(['status' => 'pending']);

        $this->actingAs($this->recruiter())
            ->get(route('admin.reports.recruitment.index', ['company_id' => $company->id]))
            ->assertOk()
            ->assertViewHas('totalApplications', 3)
            ->assertViewHas('hiredCount', 1)
            ->assertViewHas('openPostings', 1)
            ->assertViewHas('pipeline', fn ($pipeline) => $pipeline->firstWhere('status', ApplicationStatus::Applied)['count'] === 1
                && $pipeline->firstWhere('status', ApplicationStatus::Screening)['count'] === 1
                && $pipeline->firstWhere('status', ApplicationStatus::Hired)['count'] === 1)
            ->assertViewHas('requisitionsByStatus', fn ($rows) => $rows->firstWhere('status', JobRequisitionStatus::Approved)['count'] === 1
                && $rows->firstWhere('status', JobRequisitionStatus::Pending)['count'] === 1);
    }

    public function test_recruitment_report_scopes_applications_by_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $postingA = JobPosting::factory()->for($companyA, 'company')->published()->create();
        $postingB = JobPosting::factory()->for($companyB, 'company')->published()->create();

        Application::factory()->between(Applicant::factory()->create(), $postingA)->create();
        Application::factory()->between(Applicant::factory()->create(), $postingB)->create();
        Application::factory()->between(Applicant::factory()->create(), $postingB)->create();

        $this->actingAs($this->recruiter())
            ->get(route('admin.reports.recruitment.index', ['company_id' => $companyA->id]))
            ->assertOk()
            ->assertViewHas('totalApplications', 1);
    }
}
