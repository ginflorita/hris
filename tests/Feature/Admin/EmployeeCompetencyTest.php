<?php

namespace Tests\Feature\Admin;

use App\Enums\ProficiencyLevel;
use App\Models\Competency;
use App\Models\Employee;
use App\Models\EmployeeCompetency;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeCompetencyTest extends TestCase
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

    public function test_rating_an_employee_against_a_competency(): void
    {
        $user = $this->officer();
        $employee = Employee::factory()->create();
        $competency = Competency::factory()->create(['company_id' => $employee->company_id]);

        $this->actingAs($user)->post(route('admin.employees.competencies.store', $employee), [
            'competency_id' => $competency->id,
            'proficiency_level' => 'advanced',
            'assessed_at' => '2026-01-15',
        ])->assertRedirect();

        $rating = EmployeeCompetency::sole();
        $this->assertSame($employee->id, $rating->employee_id);
        $this->assertSame(ProficiencyLevel::Advanced, $rating->proficiency_level);
    }

    public function test_competency_must_belong_to_the_employees_company(): void
    {
        $user = $this->officer();
        $employee = Employee::factory()->create();
        $wrongCompetency = Competency::factory()->create();

        $this->actingAs($user)->post(route('admin.employees.competencies.store', $employee), [
            'competency_id' => $wrongCompetency->id,
            'proficiency_level' => 'beginner',
        ])->assertSessionHasErrors('competency_id');
    }

    public function test_an_employee_cannot_be_rated_twice_on_the_same_competency(): void
    {
        $user = $this->officer();
        $employee = Employee::factory()->create();
        $competency = Competency::factory()->create(['company_id' => $employee->company_id]);
        EmployeeCompetency::factory()->forEmployee($employee)->create(['competency_id' => $competency->id]);

        $this->actingAs($user)->post(route('admin.employees.competencies.store', $employee), [
            'competency_id' => $competency->id,
            'proficiency_level' => 'expert',
        ])->assertSessionHasErrors('competency_id');

        $this->assertSame(1, EmployeeCompetency::count());
    }

    public function test_updating_a_rating_can_reuse_its_own_competency(): void
    {
        $user = $this->officer();
        $employee = Employee::factory()->create();
        $competency = Competency::factory()->create(['company_id' => $employee->company_id]);
        $rating = EmployeeCompetency::factory()->forEmployee($employee)->create(['competency_id' => $competency->id]);

        $this->actingAs($user)->put(route('admin.employees.competencies.update', [$employee, $rating]), [
            'competency_id' => $competency->id,
            'proficiency_level' => 'expert',
        ])->assertRedirect()->assertSessionDoesntHaveErrors();

        $this->assertSame(ProficiencyLevel::Expert, $rating->refresh()->proficiency_level);
    }

    public function test_a_rating_from_another_employee_cannot_be_removed_through_this_one(): void
    {
        $user = $this->officer();
        $employeeA = Employee::factory()->create();
        $employeeB = Employee::factory()->create();
        $rating = EmployeeCompetency::factory()->forEmployee($employeeB)->create();

        $this->actingAs($user)->delete(route('admin.employees.competencies.destroy', [$employeeA, $rating]))
            ->assertNotFound();
    }

    public function test_requires_permission(): void
    {
        $plain = User::factory()->create();
        $employee = Employee::factory()->create();

        $this->actingAs($plain)->post(route('admin.employees.competencies.store', $employee), [])->assertForbidden();
    }
}
