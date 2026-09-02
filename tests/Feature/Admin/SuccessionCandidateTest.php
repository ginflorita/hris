<?php

namespace Tests\Feature\Admin;

use App\Enums\SuccessionReadiness;
use App\Models\Employee;
use App\Models\Position;
use App\Models\SuccessionCandidate;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuccessionCandidateTest extends TestCase
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
        $user->givePermissionTo(['performance.view', 'performance.manage']);

        return $user;
    }

    public function test_adding_a_candidacy(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $position = Position::factory()->create(['company_id' => $employee->company_id]);

        $this->actingAs($user)->post(route('admin.employees.succession-candidacies.store', $employee), [
            'position_id' => $position->id,
            'readiness' => 'ready_now',
            'notes' => 'Has covered this role during leave twice already.',
        ])->assertRedirect();

        $candidate = SuccessionCandidate::sole();
        $this->assertSame($employee->id, $candidate->employee_id);
        $this->assertSame(SuccessionReadiness::ReadyNow, $candidate->readiness);
    }

    public function test_position_must_belong_to_the_employees_company(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $wrongPosition = Position::factory()->create();

        $this->actingAs($user)->post(route('admin.employees.succession-candidacies.store', $employee), [
            'position_id' => $wrongPosition->id,
            'readiness' => 'ready_now',
        ])->assertSessionHasErrors('position_id');
    }

    public function test_an_employee_cannot_be_a_candidate_for_the_same_position_twice(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $position = Position::factory()->create(['company_id' => $employee->company_id]);
        SuccessionCandidate::factory()->forEmployee($employee)->create(['position_id' => $position->id]);

        $this->actingAs($user)->post(route('admin.employees.succession-candidacies.store', $employee), [
            'position_id' => $position->id,
            'readiness' => 'development_needed',
        ])->assertSessionHasErrors('position_id');

        $this->assertSame(1, SuccessionCandidate::count());
    }

    public function test_updating_readiness_can_reuse_its_own_position(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $position = Position::factory()->create(['company_id' => $employee->company_id]);
        $candidate = SuccessionCandidate::factory()->forEmployee($employee)->create(['position_id' => $position->id]);

        $this->actingAs($user)->put(route('admin.employees.succession-candidacies.update', [$employee, $candidate]), [
            'position_id' => $position->id,
            'readiness' => 'ready_now',
        ])->assertRedirect()->assertSessionDoesntHaveErrors();

        $this->assertSame(SuccessionReadiness::ReadyNow, $candidate->refresh()->readiness);
    }

    public function test_a_candidacy_from_another_employee_cannot_be_removed_through_this_one(): void
    {
        $user = $this->manager();
        $employeeA = Employee::factory()->create();
        $employeeB = Employee::factory()->create();
        $candidate = SuccessionCandidate::factory()->forEmployee($employeeB)->create();

        $this->actingAs($user)->delete(route('admin.employees.succession-candidacies.destroy', [$employeeA, $candidate]))
            ->assertNotFound();
    }

    public function test_removing_a_candidacy(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $candidate = SuccessionCandidate::factory()->forEmployee($employee)->create();

        $this->actingAs($user)->delete(route('admin.employees.succession-candidacies.destroy', [$employee, $candidate]))
            ->assertRedirect();

        $this->assertSame(0, SuccessionCandidate::count());
    }

    public function test_requires_permission(): void
    {
        $plain = User::factory()->create();
        $employee = Employee::factory()->create();

        $this->actingAs($plain)->post(route('admin.employees.succession-candidacies.store', $employee), [])->assertForbidden();
    }
}
