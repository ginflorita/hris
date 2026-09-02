<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\EmployeeSkill;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkillTest extends TestCase
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

    public function test_creating_a_skill(): void
    {
        $user = $this->officer();
        $company = Company::factory()->create();

        $this->actingAs($user)->post(route('admin.training.skills.store'), [
            'company_id' => $company->id,
            'name' => 'PHP',
        ])->assertRedirect();

        $skill = Skill::sole();
        $this->assertSame('PHP', $skill->name);
        $this->assertTrue($skill->is_active);
    }

    public function test_name_is_unique_per_company_only(): void
    {
        $user = $this->officer();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        Skill::factory()->create(['company_id' => $companyA->id, 'name' => 'PHP']);

        $this->actingAs($user)->post(route('admin.training.skills.store'), [
            'company_id' => $companyA->id,
            'name' => 'PHP',
        ])->assertSessionHasErrors('name');

        $this->actingAs($user)->post(route('admin.training.skills.store'), [
            'company_id' => $companyB->id,
            'name' => 'PHP',
        ])->assertRedirect()->assertSessionDoesntHaveErrors();
    }

    public function test_cannot_delete_a_skill_with_employee_ratings(): void
    {
        $user = $this->officer();
        $skill = Skill::factory()->create();
        EmployeeSkill::factory()->create(['skill_id' => $skill->id]);

        $this->actingAs($user)->delete(route('admin.training.skills.destroy', $skill))
            ->assertRedirect()
            ->assertSessionHasErrors('skill');

        $this->assertNotNull($skill->fresh());
    }

    public function test_requires_permission(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('admin.training.skills.index'))->assertForbidden();
        $this->actingAs($plain)->post(route('admin.training.skills.store'), [])->assertForbidden();
    }
}
