<?php

namespace Tests\Feature\Admin;

use App\Enums\ProficiencyLevel;
use App\Models\Employee;
use App\Models\EmployeeSkill;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeSkillTest extends TestCase
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

    public function test_rating_an_employee_against_a_skill(): void
    {
        $user = $this->officer();
        $employee = Employee::factory()->create();
        $skill = Skill::factory()->create(['company_id' => $employee->company_id]);

        $this->actingAs($user)->post(route('admin.employees.skills.store', $employee), [
            'skill_id' => $skill->id,
            'proficiency_level' => 'beginner',
        ])->assertRedirect();

        $rating = EmployeeSkill::sole();
        $this->assertSame($employee->id, $rating->employee_id);
        $this->assertSame(ProficiencyLevel::Beginner, $rating->proficiency_level);
    }

    public function test_skill_must_belong_to_the_employees_company(): void
    {
        $user = $this->officer();
        $employee = Employee::factory()->create();
        $wrongSkill = Skill::factory()->create();

        $this->actingAs($user)->post(route('admin.employees.skills.store', $employee), [
            'skill_id' => $wrongSkill->id,
            'proficiency_level' => 'beginner',
        ])->assertSessionHasErrors('skill_id');
    }

    public function test_an_employee_cannot_be_rated_twice_on_the_same_skill(): void
    {
        $user = $this->officer();
        $employee = Employee::factory()->create();
        $skill = Skill::factory()->create(['company_id' => $employee->company_id]);
        EmployeeSkill::factory()->forEmployee($employee)->create(['skill_id' => $skill->id]);

        $this->actingAs($user)->post(route('admin.employees.skills.store', $employee), [
            'skill_id' => $skill->id,
            'proficiency_level' => 'expert',
        ])->assertSessionHasErrors('skill_id');

        $this->assertSame(1, EmployeeSkill::count());
    }

    public function test_removing_a_rating(): void
    {
        $user = $this->officer();
        $employee = Employee::factory()->create();
        $rating = EmployeeSkill::factory()->forEmployee($employee)->create();

        $this->actingAs($user)->delete(route('admin.employees.skills.destroy', [$employee, $rating]))
            ->assertRedirect();

        $this->assertSame(0, EmployeeSkill::count());
    }

    public function test_requires_permission(): void
    {
        $plain = User::factory()->create();
        $employee = Employee::factory()->create();

        $this->actingAs($plain)->post(route('admin.employees.skills.store', $employee), [])->assertForbidden();
    }
}
