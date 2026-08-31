<?php

namespace Tests\Feature\Admin\Recruitment;

use App\Enums\JobPostingStatus;
use App\Models\JobPosting;
use App\Models\JobRequisition;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobPostingTest extends TestCase
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

    public function test_a_posting_can_only_be_created_against_an_approved_requisition(): void
    {
        $user = $this->recruiter();
        $pending = JobRequisition::factory()->create();

        $this->actingAs($user)->post(route('admin.recruitment.postings.store'), [
            'job_requisition_id' => $pending->id,
            'title' => 'Backend Engineer',
        ])->assertSessionHasErrors('job_requisition_id');

        $this->assertSame(0, JobPosting::count());

        $approved = JobRequisition::factory()->approved()->create();
        $this->actingAs($user)->post(route('admin.recruitment.postings.store'), [
            'job_requisition_id' => $approved->id,
            'title' => 'Backend Engineer',
        ])->assertRedirect();

        $posting = JobPosting::sole();
        $this->assertSame(JobPostingStatus::Draft, $posting->status);
        $this->assertSame($approved->company_id, $posting->company_id);
    }

    public function test_publish_and_close_follow_the_draft_published_closed_lifecycle(): void
    {
        $user = $this->recruiter();
        $requisition = JobRequisition::factory()->approved()->create();
        $posting = JobPosting::factory()->forRequisition($requisition)->create();

        $this->actingAs($user)->put(route('admin.recruitment.postings.close', $posting))->assertStatus(422);

        $this->actingAs($user)->put(route('admin.recruitment.postings.publish', $posting))->assertRedirect();
        $posting->refresh();
        $this->assertSame(JobPostingStatus::Published, $posting->status);
        $this->assertNotNull($posting->published_at);

        $this->actingAs($user)->put(route('admin.recruitment.postings.publish', $posting))->assertStatus(422);

        $this->actingAs($user)->put(route('admin.recruitment.postings.close', $posting))->assertRedirect();
        $this->assertSame(JobPostingStatus::Closed, $posting->refresh()->status);
    }

    public function test_updating_a_posting_does_not_change_its_requisition(): void
    {
        $user = $this->recruiter();
        $requisition = JobRequisition::factory()->approved()->create();
        $posting = JobPosting::factory()->forRequisition($requisition)->create(['title' => 'Old Title']);

        $this->actingAs($user)->put(route('admin.recruitment.postings.update', $posting), [
            'title' => 'New Title',
            'is_internal' => '1',
        ])->assertRedirect();

        $posting->refresh();
        $this->assertSame('New Title', $posting->title);
        $this->assertTrue($posting->is_internal);
        $this->assertSame($requisition->id, $posting->job_requisition_id);
    }

    public function test_requires_permission(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('admin.recruitment.postings.index'))->assertForbidden();
    }
}
