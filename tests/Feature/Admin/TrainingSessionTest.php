<?php

namespace Tests\Feature\Admin;

use App\Enums\TrainingSessionStatus;
use App\Models\TrainingCourse;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function officer(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['training.view', 'training.manage']);

        return $user;
    }

    public function test_adding_a_session_defaults_to_scheduled_and_inherits_the_courses_company(): void
    {
        $user = $this->officer();
        $course = TrainingCourse::factory()->create();

        $this->actingAs($user)->post(route('admin.training.courses.sessions.store', $course), [
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-03',
            'location' => 'HQ Training Room',
            'capacity' => 20,
            'cost' => 1500,
        ])->assertRedirect();

        $session = TrainingSession::sole();
        $this->assertSame($course->id, $session->training_course_id);
        $this->assertSame($course->company_id, $session->company_id);
        $this->assertSame(TrainingSessionStatus::Scheduled, $session->status);
    }

    public function test_complete_and_cancel_are_only_reachable_from_scheduled(): void
    {
        $user = $this->officer();
        $course = TrainingCourse::factory()->create();
        $session = TrainingSession::factory()->forCourse($course)->create();

        $this->actingAs($user)->put(route('admin.training.courses.sessions.complete', [$course, $session]))
            ->assertRedirect();
        $this->assertSame(TrainingSessionStatus::Completed, $session->refresh()->status);

        $this->actingAs($user)->put(route('admin.training.courses.sessions.cancel', [$course, $session]))
            ->assertStatus(422);
    }

    public function test_a_completed_session_cannot_be_edited_or_removed(): void
    {
        $user = $this->officer();
        $course = TrainingCourse::factory()->create();
        $session = TrainingSession::factory()->forCourse($course)->create(['status' => TrainingSessionStatus::Completed]);

        $this->actingAs($user)->put(route('admin.training.courses.sessions.update', [$course, $session]), [
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-02',
        ])->assertStatus(422);

        $this->actingAs($user)->delete(route('admin.training.courses.sessions.destroy', [$course, $session]))
            ->assertStatus(422);
    }

    public function test_a_session_from_another_course_cannot_be_acted_on_through_this_one(): void
    {
        $user = $this->officer();
        $courseA = TrainingCourse::factory()->create();
        $courseB = TrainingCourse::factory()->create();
        $session = TrainingSession::factory()->forCourse($courseB)->create();

        $this->actingAs($user)->put(route('admin.training.courses.sessions.complete', [$courseA, $session]))
            ->assertNotFound();
    }

    public function test_end_date_cannot_precede_start_date(): void
    {
        $user = $this->officer();
        $course = TrainingCourse::factory()->create();

        $this->actingAs($user)->post(route('admin.training.courses.sessions.store', $course), [
            'start_date' => '2026-07-10',
            'end_date' => '2026-07-05',
        ])->assertSessionHasErrors('end_date');
    }

    public function test_requires_permission(): void
    {
        $plain = User::factory()->create();
        $course = TrainingCourse::factory()->create();

        $this->actingAs($plain)->post(route('admin.training.courses.sessions.store', $course), [])->assertForbidden();
    }
}
