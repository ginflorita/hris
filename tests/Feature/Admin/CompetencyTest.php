<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\Competency;
use App\Models\EmployeeCompetency;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetencyTest extends TestCase
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

    public function test_creating_a_competency(): void
    {
        $user = $this->officer();
        $company = Company::factory()->create();

        $this->actingAs($user)->post(route('admin.training.competencies.store'), [
            'company_id' => $company->id,
            'name' => 'Leadership',
            'description' => 'Ability to guide and motivate a team.',
        ])->assertRedirect();

        $competency = Competency::sole();
        $this->assertSame('Leadership', $competency->name);
        $this->assertTrue($competency->is_active);
    }

    public function test_name_is_unique_per_company_only(): void
    {
        $user = $this->officer();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        Competency::factory()->create(['company_id' => $companyA->id, 'name' => 'Leadership']);

        $this->actingAs($user)->post(route('admin.training.competencies.store'), [
            'company_id' => $companyA->id,
            'name' => 'Leadership',
        ])->assertSessionHasErrors('name');

        $this->actingAs($user)->post(route('admin.training.competencies.store'), [
            'company_id' => $companyB->id,
            'name' => 'Leadership',
        ])->assertRedirect()->assertSessionDoesntHaveErrors();
    }

    public function test_cannot_delete_a_competency_with_employee_ratings(): void
    {
        $user = $this->officer();
        $competency = Competency::factory()->create();
        EmployeeCompetency::factory()->create(['competency_id' => $competency->id]);

        $this->actingAs($user)->delete(route('admin.training.competencies.destroy', $competency))
            ->assertRedirect()
            ->assertSessionHasErrors('competency');

        $this->assertNotNull($competency->fresh());
    }

    public function test_requires_permission(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('admin.training.competencies.index'))->assertForbidden();
        $this->actingAs($plain)->post(route('admin.training.competencies.store'), [])->assertForbidden();
    }
}
