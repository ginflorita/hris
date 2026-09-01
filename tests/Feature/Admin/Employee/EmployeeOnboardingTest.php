<?php

namespace Tests\Feature\Admin\Employee;

use App\Models\Employee;
use App\Models\EmployeeOnboarding;
use App\Models\EmployeeOnboardingTask;
use App\Models\OnboardingTask;
use App\Models\OnboardingTemplate;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function manager(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['employees.view', 'employees.update']);

        return $user;
    }

    public function test_assigning_a_template_snapshots_its_tasks(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $template = OnboardingTemplate::factory()->create(['company_id' => $employee->company_id]);
        OnboardingTask::factory()->forTemplate($template)->create(['title' => 'Sign contract', 'sequence' => 1]);
        OnboardingTask::factory()->forTemplate($template)->create(['title' => 'Orientation', 'sequence' => 2]);

        $this->actingAs($user)->post(route('admin.employees.onboardings.store', $employee), [
            'onboarding_template_id' => $template->id,
            'notes' => 'Starts Monday.',
        ])->assertRedirect();

        $onboarding = EmployeeOnboarding::sole();
        $this->assertSame($employee->id, $onboarding->employee_id);
        $this->assertSame($user->id, $onboarding->assigned_by);
        $this->assertSame(2, $onboarding->tasks()->count());
        $this->assertSame(['Sign contract', 'Orientation'], $onboarding->tasks->pluck('title')->all());
        $this->assertTrue($onboarding->tasks->every(fn ($t) => $t->is_completed === false));
    }

    public function test_editing_the_template_later_does_not_change_an_already_assigned_checklist(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $template = OnboardingTemplate::factory()->create(['company_id' => $employee->company_id]);
        OnboardingTask::factory()->forTemplate($template)->create(['title' => 'Original task']);

        $this->actingAs($user)->post(route('admin.employees.onboardings.store', $employee), [
            'onboarding_template_id' => $template->id,
        ])->assertRedirect();

        $template->tasks()->first()->update(['title' => 'Renamed task']);
        OnboardingTask::factory()->forTemplate($template)->create(['title' => 'Added later']);

        $onboarding = EmployeeOnboarding::sole();
        $this->assertSame(['Original task'], $onboarding->tasks->pluck('title')->all());
    }

    public function test_cannot_assign_a_second_template_while_one_is_incomplete(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $templateA = OnboardingTemplate::factory()->create(['company_id' => $employee->company_id]);
        $templateB = OnboardingTemplate::factory()->create(['company_id' => $employee->company_id]);

        $onboarding = EmployeeOnboarding::factory()->forEmployee($employee)->create(['onboarding_template_id' => $templateA->id]);
        EmployeeOnboardingTask::factory()->forOnboarding($onboarding)->create(['is_completed' => false]);

        $this->actingAs($user)->post(route('admin.employees.onboardings.store', $employee), [
            'onboarding_template_id' => $templateB->id,
        ])->assertStatus(422);

        $this->assertSame(1, EmployeeOnboarding::count());
    }

    public function test_can_assign_a_new_template_once_the_previous_one_is_complete(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $templateA = OnboardingTemplate::factory()->create(['company_id' => $employee->company_id]);
        $templateB = OnboardingTemplate::factory()->create(['company_id' => $employee->company_id]);

        $onboarding = EmployeeOnboarding::factory()->forEmployee($employee)->create(['onboarding_template_id' => $templateA->id]);
        EmployeeOnboardingTask::factory()->forOnboarding($onboarding)->completed()->create();

        $this->actingAs($user)->post(route('admin.employees.onboardings.store', $employee), [
            'onboarding_template_id' => $templateB->id,
        ])->assertRedirect();

        $this->assertSame(2, EmployeeOnboarding::count());
    }

    public function test_template_must_be_active_and_belong_to_the_employees_company(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $inactive = OnboardingTemplate::factory()->create(['company_id' => $employee->company_id, 'is_active' => false]);
        $otherCompany = OnboardingTemplate::factory()->create();

        $this->actingAs($user)->post(route('admin.employees.onboardings.store', $employee), [
            'onboarding_template_id' => $inactive->id,
        ])->assertSessionHasErrors('onboarding_template_id');

        $this->actingAs($user)->post(route('admin.employees.onboardings.store', $employee), [
            'onboarding_template_id' => $otherCompany->id,
        ])->assertSessionHasErrors('onboarding_template_id');
    }

    public function test_toggling_a_task_stamps_and_clears_completion(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $onboarding = EmployeeOnboarding::factory()->forEmployee($employee)->create();
        $task = EmployeeOnboardingTask::factory()->forOnboarding($onboarding)->create();

        $this->actingAs($user)->put(route('admin.employees.onboardings.tasks.update', [$employee, $onboarding, $task]), [
            'is_completed' => '1',
        ])->assertRedirect();

        $task->refresh();
        $this->assertTrue($task->is_completed);
        $this->assertNotNull($task->completed_at);
        $this->assertSame($user->id, $task->completed_by);

        $this->actingAs($user)->put(route('admin.employees.onboardings.tasks.update', [$employee, $onboarding, $task]), [
            'is_completed' => '0',
        ])->assertRedirect();

        $task->refresh();
        $this->assertFalse($task->is_completed);
        $this->assertNull($task->completed_at);
        $this->assertNull($task->completed_by);
    }

    public function test_progress_percentage_and_completeness(): void
    {
        $onboarding = EmployeeOnboarding::factory()->create();
        EmployeeOnboardingTask::factory()->forOnboarding($onboarding)->completed()->create();
        EmployeeOnboardingTask::factory()->forOnboarding($onboarding)->create(['is_completed' => false]);

        $onboarding->refresh();
        $this->assertSame(50, $onboarding->progressPercentage());
        $this->assertFalse($onboarding->isComplete());

        $onboarding->tasks()->update(['is_completed' => true]);
        $onboarding->refresh();
        $this->assertSame(100, $onboarding->progressPercentage());
        $this->assertTrue($onboarding->isComplete());
    }

    public function test_a_task_from_another_employees_onboarding_cannot_be_toggled_through_this_one(): void
    {
        $user = $this->manager();
        $employeeA = Employee::factory()->create();
        $employeeB = Employee::factory()->create();
        $onboardingB = EmployeeOnboarding::factory()->forEmployee($employeeB)->create();
        $taskB = EmployeeOnboardingTask::factory()->forOnboarding($onboardingB)->create();

        $this->actingAs($user)->put(route('admin.employees.onboardings.tasks.update', [$employeeA, $onboardingB, $taskB]), [
            'is_completed' => '1',
        ])->assertNotFound();
    }

    public function test_requires_permission(): void
    {
        $plain = User::factory()->create();
        $employee = Employee::factory()->create();

        $this->actingAs($plain)->post(route('admin.employees.onboardings.store', $employee), [])->assertForbidden();
    }
}
