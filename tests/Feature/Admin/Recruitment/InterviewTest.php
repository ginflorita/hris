<?php

namespace Tests\Feature\Admin\Recruitment;

use App\Enums\InterviewRecommendation;
use App\Enums\InterviewStatus;
use App\Models\Application;
use App\Models\Employee;
use App\Models\Interview;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterviewTest extends TestCase
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

    public function test_scheduling_an_interview_creates_it_as_scheduled(): void
    {
        $user = $this->recruiter();
        $application = Application::factory()->create();
        $interviewer = Employee::factory()->create();

        $this->actingAs($user)->post(route('admin.recruitment.applications.interviews.store', $application), [
            'interviewer_id' => $interviewer->id,
            'type' => 'technical',
            'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'location' => 'Google Meet',
        ])->assertRedirect();

        $interview = Interview::sole();
        $this->assertSame($application->id, $interview->application_id);
        $this->assertSame($interviewer->id, $interview->interviewer_id);
        $this->assertSame(InterviewStatus::Scheduled, $interview->status);
    }

    public function test_recording_an_outcome_updates_status_rating_and_recommendation(): void
    {
        $user = $this->recruiter();
        $application = Application::factory()->create();
        $interview = Interview::factory()->forApplication($application)->create();

        $this->actingAs($user)->put(route('admin.recruitment.applications.interviews.update', [$application, $interview]), [
            'scheduled_at' => $interview->scheduled_at->format('Y-m-d H:i:s'),
            'status' => 'completed',
            'rating' => 4,
            'recommendation' => 'yes',
            'feedback' => 'Strong technical fundamentals.',
        ])->assertRedirect();

        $interview->refresh();
        $this->assertSame(InterviewStatus::Completed, $interview->status);
        $this->assertSame(4, $interview->rating);
        $this->assertSame(InterviewRecommendation::Yes, $interview->recommendation);
        $this->assertSame('Strong technical fundamentals.', $interview->feedback);
    }

    public function test_an_interview_from_another_application_cannot_be_updated_through_this_one(): void
    {
        $user = $this->recruiter();
        $applicationA = Application::factory()->create();
        $applicationB = Application::factory()->create();
        $interview = Interview::factory()->forApplication($applicationB)->create();

        $this->actingAs($user)->put(route('admin.recruitment.applications.interviews.update', [$applicationA, $interview]), [
            'scheduled_at' => now()->format('Y-m-d H:i:s'),
            'status' => 'completed',
        ])->assertNotFound();
    }

    public function test_requires_permission(): void
    {
        $plain = User::factory()->create();
        $application = Application::factory()->create();

        $this->actingAs($plain)->post(route('admin.recruitment.applications.interviews.store', $application), [])->assertForbidden();
    }
}
