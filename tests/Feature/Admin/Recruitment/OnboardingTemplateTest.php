<?php

namespace Tests\Feature\Admin\Recruitment;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeOnboarding;
use App\Models\OnboardingTask;
use App\Models\OnboardingTemplate;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTemplateTest extends TestCase
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

    public function test_creating_a_template_defaults_to_active(): void
    {
        $user = $this->recruiter();
        $company = Company::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.recruitment.onboarding-templates.store'), [
            'company_id' => $company->id,
            'name' => 'New Employee Onboarding',
        ]);

        $template = OnboardingTemplate::sole();
        $response->assertRedirect(route('admin.recruitment.onboarding-templates.show', $template));
        $this->assertTrue($template->is_active);
        $this->assertSame($company->id, $template->company_id);
    }

    public function test_unchecking_active_on_update_actually_deactivates(): void
    {
        $user = $this->recruiter();
        $template = OnboardingTemplate::factory()->create(['is_active' => true]);

        $this->actingAs($user)->put(route('admin.recruitment.onboarding-templates.update', $template), [
            'company_id' => $template->company_id,
            'name' => $template->name,
            // is_active omitted, as an unchecked checkbox would submit
        ])->assertRedirect();

        $this->assertFalse($template->refresh()->is_active);
    }

    public function test_adding_editing_and_removing_tasks(): void
    {
        $user = $this->recruiter();
        $template = OnboardingTemplate::factory()->create();

        $this->actingAs($user)->post(route('admin.recruitment.onboarding-templates.tasks.store', $template), [
            'title' => 'Sign contract',
            'sequence' => 2,
        ])->assertRedirect();

        $task = OnboardingTask::sole();
        $this->assertSame('Sign contract', $task->title);
        $this->assertSame(2, $task->sequence);

        $this->actingAs($user)->put(route('admin.recruitment.onboarding-templates.tasks.update', [$template, $task]), [
            'title' => 'Sign employment contract',
            'sequence' => 1,
        ])->assertRedirect();
        $this->assertSame('Sign employment contract', $task->refresh()->title);

        $this->actingAs($user)->delete(route('admin.recruitment.onboarding-templates.tasks.destroy', [$template, $task]))
            ->assertRedirect();
        $this->assertSame(0, OnboardingTask::count());
    }

    public function test_a_task_from_another_template_cannot_be_edited_through_this_one(): void
    {
        $user = $this->recruiter();
        $templateA = OnboardingTemplate::factory()->create();
        $templateB = OnboardingTemplate::factory()->create();
        $task = OnboardingTask::factory()->forTemplate($templateB)->create();

        $this->actingAs($user)->put(route('admin.recruitment.onboarding-templates.tasks.update', [$templateA, $task]), [
            'title' => 'Hijacked',
        ])->assertNotFound();
    }

    public function test_deleting_a_template_already_assigned_to_an_employee_is_blocked(): void
    {
        $user = $this->recruiter();
        $template = OnboardingTemplate::factory()->create();
        EmployeeOnboarding::factory()->create(['onboarding_template_id' => $template->id, 'employee_id' => Employee::factory()->create()->id]);

        $this->actingAs($user)->delete(route('admin.recruitment.onboarding-templates.destroy', $template))
            ->assertStatus(422);

        $this->assertNotNull($template->fresh());
    }

    public function test_requires_permission(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('admin.recruitment.onboarding-templates.index'))->assertForbidden();
        $this->actingAs($plain)->post(route('admin.recruitment.onboarding-templates.store'), [])->assertForbidden();
    }
}
