<?php

namespace Tests\Feature\Admin\Reports;

use App\Enums\TrainingEnrollmentStatus;
use App\Models\Company;
use App\Models\TrainingCourse;
use App\Models\TrainingEnrollment;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function trainingAdmin(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('training.view');

        return $user;
    }

    public function test_training_report_requires_training_view_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('reports.view');

        $this->actingAs($user)->get(route('admin.reports.training.index'))->assertForbidden();
        $this->actingAs($this->trainingAdmin())->get(route('admin.reports.training.index'))->assertOk();
    }

    public function test_training_report_computes_completion_rate_and_certificate_counts(): void
    {
        $company = Company::factory()->create();
        $course = TrainingCourse::factory()->for($company, 'company')->create();
        $session = TrainingSession::factory()->forCourse($course)->create();

        TrainingEnrollment::factory()->forSession($session)->create(['status' => 'completed', 'certificate_number' => 'CERT-1', 'certificate_expires_at' => now()->addDays(10)]);
        TrainingEnrollment::factory()->forSession($session)->create(['status' => 'completed', 'certificate_number' => 'CERT-2', 'certificate_expires_at' => now()->addDays(90)]);
        TrainingEnrollment::factory()->forSession($session)->create(['status' => 'no_show']);
        TrainingEnrollment::factory()->forSession($session)->create(['status' => 'enrolled']);

        $this->actingAs($this->trainingAdmin())
            ->get(route('admin.reports.training.index', ['company_id' => $company->id]))
            ->assertOk()
            ->assertViewHas('totalEnrollments', 4)
            ->assertViewHas('completionRate', 50.0)
            ->assertViewHas('certificatesIssued', 2)
            ->assertViewHas('certificatesExpiringSoon', 1)
            ->assertViewHas('byStatus', fn ($rows) => $rows->firstWhere('status', TrainingEnrollmentStatus::Completed)['count'] === 2
                && $rows->firstWhere('status', TrainingEnrollmentStatus::NoShow)['count'] === 1
                && $rows->firstWhere('status', TrainingEnrollmentStatus::Enrolled)['count'] === 1);
    }

    public function test_training_report_scopes_enrollments_by_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $sessionA = TrainingSession::factory()->forCourse(TrainingCourse::factory()->for($companyA, 'company')->create())->create();
        $sessionB = TrainingSession::factory()->forCourse(TrainingCourse::factory()->for($companyB, 'company')->create())->create();

        TrainingEnrollment::factory()->forSession($sessionA)->create();
        TrainingEnrollment::factory()->forSession($sessionB)->count(2)->create();

        $this->actingAs($this->trainingAdmin())
            ->get(route('admin.reports.training.index', ['company_id' => $companyA->id]))
            ->assertOk()
            ->assertViewHas('totalEnrollments', 1);
    }
}
