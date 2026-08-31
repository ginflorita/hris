<?php

namespace Tests\Feature\Admin\Recruitment;

use App\Models\Application;
use App\Models\Assessment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentTest extends TestCase
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

    public function test_assigning_an_assessment(): void
    {
        $user = $this->recruiter();
        $application = Application::factory()->create();

        $this->actingAs($user)->post(route('admin.recruitment.applications.assessments.store', $application), [
            'type' => 'coding',
            'due_at' => now()->addWeek()->toDateString(),
            'notes' => 'Take-home coding exercise.',
        ])->assertRedirect();

        $assessment = Assessment::sole();
        $this->assertSame($application->id, $assessment->application_id);
        $this->assertNull($assessment->completed_at);
        $this->assertNull($assessment->passed);
    }

    public function test_recording_a_result_stamps_assessed_by(): void
    {
        $user = $this->recruiter();
        $application = Application::factory()->create();
        $assessment = Assessment::factory()->forApplication($application)->create();

        $this->actingAs($user)->put(route('admin.recruitment.applications.assessments.update', [$application, $assessment]), [
            'completed_at' => now()->toDateString(),
            'score' => 87.5,
            'passed' => '1',
            'notes' => 'Solid submission.',
        ])->assertRedirect();

        $assessment->refresh();
        $this->assertNotNull($assessment->completed_at);
        $this->assertSame('87.50', (string) $assessment->score);
        $this->assertTrue($assessment->passed);
        $this->assertSame($user->id, $assessment->assessed_by);
    }

    public function test_saving_without_completing_does_not_stamp_assessed_by(): void
    {
        $user = $this->recruiter();
        $application = Application::factory()->create();
        $assessment = Assessment::factory()->forApplication($application)->create();

        $this->actingAs($user)->put(route('admin.recruitment.applications.assessments.update', [$application, $assessment]), [
            'notes' => 'Reminder sent to candidate.',
        ])->assertRedirect();

        $this->assertNull($assessment->refresh()->assessed_by);
    }

    public function test_an_assessment_from_another_application_cannot_be_updated_through_this_one(): void
    {
        $user = $this->recruiter();
        $applicationA = Application::factory()->create();
        $applicationB = Application::factory()->create();
        $assessment = Assessment::factory()->forApplication($applicationB)->create();

        $this->actingAs($user)->put(route('admin.recruitment.applications.assessments.update', [$applicationA, $assessment]), [
            'score' => 50,
        ])->assertNotFound();
    }

    public function test_requires_permission(): void
    {
        $plain = User::factory()->create();
        $application = Application::factory()->create();

        $this->actingAs($plain)->post(route('admin.recruitment.applications.assessments.store', $application), [])->assertForbidden();
    }
}
