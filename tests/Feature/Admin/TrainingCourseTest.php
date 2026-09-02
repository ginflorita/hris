<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\TrainingCourse;
use App\Models\TrainingProvider;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingCourseTest extends TestCase
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

    public function test_creating_a_course(): void
    {
        $user = $this->officer();
        $company = Company::factory()->create();
        $provider = TrainingProvider::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->post(route('admin.training.courses.store'), [
            'company_id' => $company->id,
            'training_provider_id' => $provider->id,
            'name' => 'Advanced Laravel',
            'duration_hours' => 16,
        ]);

        $course = TrainingCourse::sole();
        $response->assertRedirect(route('admin.training.courses.show', $course));
        $this->assertSame($provider->id, $course->training_provider_id);
    }

    public function test_provider_must_belong_to_the_selected_company(): void
    {
        $user = $this->officer();
        $company = Company::factory()->create();
        $wrongProvider = TrainingProvider::factory()->create();

        $this->actingAs($user)->post(route('admin.training.courses.store'), [
            'company_id' => $company->id,
            'training_provider_id' => $wrongProvider->id,
            'name' => 'Advanced Laravel',
        ])->assertSessionHasErrors('training_provider_id');
    }

    public function test_cannot_delete_a_course_with_sessions(): void
    {
        $user = $this->officer();
        $course = TrainingCourse::factory()->create();
        TrainingSession::factory()->forCourse($course)->create();

        $this->actingAs($user)->delete(route('admin.training.courses.destroy', $course))
            ->assertRedirect()
            ->assertSessionHasErrors('course');

        $this->assertNotNull($course->fresh());
    }

    public function test_show_page_lists_sessions(): void
    {
        $user = $this->officer();
        $course = TrainingCourse::factory()->create();
        TrainingSession::factory()->forCourse($course)->create();

        $this->actingAs($user)->get(route('admin.training.courses.show', $course))
            ->assertOk()
            ->assertViewHas('sessions', fn ($sessions) => $sessions->count() === 1);
    }

    public function test_requires_permission(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('admin.training.courses.index'))->assertForbidden();
        $this->actingAs($plain)->post(route('admin.training.courses.store'), [])->assertForbidden();
    }
}
