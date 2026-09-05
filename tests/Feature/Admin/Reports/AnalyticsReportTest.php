<?php

namespace Tests\Feature\Admin\Reports;

use App\Models\Company;
use App\Models\Employee;
use App\Models\JobPosting;
use App\Models\JobRequisition;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\PerformanceReview;
use App\Models\TrainingEnrollment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function viewer(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('reports.view');

        return $user;
    }

    public function test_analytics_report_requires_reports_view_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.reports.analytics.index'))->assertForbidden();
        $this->actingAs($this->viewer())->get(route('admin.reports.analytics.index'))->assertOk();
    }

    public function test_analytics_report_aggregates_top_line_metrics_for_the_company(): void
    {
        $company = Company::factory()->create();

        Employee::factory()->for($company, 'company')->count(3)->create();
        Employee::factory()->for($company, 'company')->create(['archived_at' => now()]);

        JobPosting::factory()->for($company, 'company')->published()->create();
        JobRequisition::factory()->for($company, 'company')->create(['status' => 'pending']);

        LeaveRequest::factory()->for($company, 'company')->create(['status' => 'pending']);
        LeaveRequest::factory()->for($company, 'company')->create(['status' => 'approved']);

        OvertimeRequest::factory()->create(['company_id' => $company->id, 'status' => 'pending']);

        $emp1 = Employee::factory()->for($company, 'company')->create();
        PerformanceReview::factory()->forEmployee($emp1)->create(['rating' => 4]);
        PerformanceReview::factory()->forEmployee($emp1)->create(['rating' => 2]);

        $enrollmentCompany = TrainingEnrollment::factory()->create(['status' => 'completed']);
        $enrollmentCompany->session->update(['company_id' => $company->id]);
        $enrollmentOther = TrainingEnrollment::factory()->create(['status' => 'no_show']);
        $enrollmentOther->session->update(['company_id' => $company->id]);

        $this->actingAs($this->viewer())
            ->get(route('admin.reports.analytics.index', ['company_id' => $company->id]))
            ->assertOk()
            ->assertViewHas('activeEmployees', 4)
            ->assertViewHas('openPostings', 1)
            ->assertViewHas('pendingRequisitions', 1)
            ->assertViewHas('pendingLeaveRequests', 1)
            ->assertViewHas('pendingOvertimeRequests', 1)
            ->assertViewHas('averagePerformanceRating', 3.0)
            ->assertViewHas('trainingCompletionRate', 50.0);
    }

    public function test_analytics_report_handles_a_company_with_no_data(): void
    {
        $company = Company::factory()->create();

        $this->actingAs($this->viewer())
            ->get(route('admin.reports.analytics.index', ['company_id' => $company->id]))
            ->assertOk()
            ->assertViewHas('activeEmployees', 0)
            ->assertViewHas('averagePerformanceRating', fn ($value) => $value === null)
            ->assertViewHas('trainingCompletionRate', fn ($value) => $value === null);
    }
}
