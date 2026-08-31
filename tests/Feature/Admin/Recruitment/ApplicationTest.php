<?php

namespace Tests\Feature\Admin\Recruitment;

use App\Enums\ApplicationStatus;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Assessment;
use App\Models\Interview;
use App\Models\JobPosting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationTest extends TestCase
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

    public function test_can_only_apply_to_a_published_posting(): void
    {
        $user = $this->recruiter();
        $applicant = Applicant::factory()->create();
        $draft = JobPosting::factory()->create();

        $this->actingAs($user)->post(route('admin.recruitment.applicants.applications.store', $applicant), [
            'job_posting_id' => $draft->id,
        ])->assertSessionHasErrors('job_posting_id');

        $this->assertSame(0, Application::count());

        $published = JobPosting::factory()->published()->create();
        $this->actingAs($user)->post(route('admin.recruitment.applicants.applications.store', $applicant), [
            'job_posting_id' => $published->id,
        ])->assertRedirect();

        $application = Application::sole();
        $this->assertSame(ApplicationStatus::Applied, $application->status);
    }

    public function test_the_same_applicant_cannot_apply_to_the_same_posting_twice(): void
    {
        $user = $this->recruiter();
        $applicant = Applicant::factory()->create();
        $posting = JobPosting::factory()->published()->create();
        Application::factory()->between($applicant, $posting)->create();

        $this->actingAs($user)->post(route('admin.recruitment.applicants.applications.store', $applicant), [
            'job_posting_id' => $posting->id,
        ])->assertSessionHasErrors('job_posting_id');

        $this->assertSame(1, Application::count());
    }

    public function test_status_moves_forward_and_rejecting_requires_a_reason(): void
    {
        $user = $this->recruiter();
        $application = Application::factory()->create();

        $this->actingAs($user)->put(route('admin.recruitment.applications.status', $application), [
            'status' => 'screening',
        ])->assertRedirect();
        $this->assertSame(ApplicationStatus::Screening, $application->refresh()->status);

        $this->actingAs($user)->put(route('admin.recruitment.applications.status', $application), [
            'status' => 'rejected',
        ])->assertSessionHasErrors('rejection_reason');

        $this->actingAs($user)->put(route('admin.recruitment.applications.status', $application), [
            'status' => 'rejected',
            'rejection_reason' => 'Not enough relevant experience.',
        ])->assertRedirect();

        $application->refresh();
        $this->assertSame(ApplicationStatus::Rejected, $application->status);
        $this->assertSame('Not enough relevant experience.', $application->rejection_reason);
    }

    public function test_a_terminal_status_cannot_be_changed_again(): void
    {
        $user = $this->recruiter();
        $application = Application::factory()->create(['status' => ApplicationStatus::Hired]);

        $this->actingAs($user)->put(route('admin.recruitment.applications.status', $application), [
            'status' => 'screening',
        ])->assertStatus(422);
    }

    public function test_index_filters_by_status_and_posting(): void
    {
        $user = $this->recruiter();
        $postingA = JobPosting::factory()->published()->create(['title' => 'Posting A']);
        $postingB = JobPosting::factory()->published()->create(['title' => 'Posting B']);
        Application::factory()->create(['job_posting_id' => $postingA->id, 'status' => ApplicationStatus::Applied]);
        Application::factory()->create(['job_posting_id' => $postingB->id, 'status' => ApplicationStatus::Hired]);

        $response = $this->actingAs($user)->get(route('admin.recruitment.applications.index', ['job_posting_id' => $postingA->id]));

        $response->assertOk();
        $ids = $response->viewData('applications')->pluck('job_posting_id')->all();
        $this->assertSame([$postingA->id], $ids);
    }

    public function test_show_page_lists_interviews_and_assessments(): void
    {
        $user = $this->recruiter();
        $application = Application::factory()->create();
        Interview::factory()->forApplication($application)->create();
        Assessment::factory()->forApplication($application)->create();

        $response = $this->actingAs($user)->get(route('admin.recruitment.applications.show', $application));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('application')->interviews);
        $this->assertCount(1, $response->viewData('application')->assessments);
    }
}
