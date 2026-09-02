<?php

namespace Tests\Feature\Admin;

use App\Models\BenefitEnrollment;
use App\Models\BenefitPlan;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BenefitPlanTest extends TestCase
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
        $user->givePermissionTo(['benefits.view', 'benefits.manage']);

        return $user;
    }

    public function test_creating_a_plan(): void
    {
        $user = $this->officer();
        $company = Company::factory()->create();

        $this->actingAs($user)->post(route('admin.benefits.plans.store'), [
            'company_id' => $company->id,
            'name' => 'Group HMO',
            'type' => 'hmo',
        ])->assertRedirect();

        $plan = BenefitPlan::sole();
        $this->assertSame('Group HMO', $plan->name);
        $this->assertTrue($plan->is_active);
    }

    public function test_name_is_unique_per_company_only(): void
    {
        $user = $this->officer();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        BenefitPlan::factory()->create(['company_id' => $companyA->id, 'name' => 'Group HMO']);

        $this->actingAs($user)->post(route('admin.benefits.plans.store'), [
            'company_id' => $companyA->id,
            'name' => 'Group HMO',
            'type' => 'hmo',
        ])->assertSessionHasErrors('name');

        $this->actingAs($user)->post(route('admin.benefits.plans.store'), [
            'company_id' => $companyB->id,
            'name' => 'Group HMO',
            'type' => 'hmo',
        ])->assertRedirect()->assertSessionDoesntHaveErrors();
    }

    public function test_cannot_delete_a_plan_with_enrollments(): void
    {
        $user = $this->officer();
        $plan = BenefitPlan::factory()->create();
        BenefitEnrollment::factory()->create(['benefit_plan_id' => $plan->id]);

        $this->actingAs($user)->delete(route('admin.benefits.plans.destroy', $plan))
            ->assertRedirect()
            ->assertSessionHasErrors('plan');

        $this->assertNotNull($plan->fresh());
    }

    public function test_requires_permission(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('admin.benefits.plans.index'))->assertForbidden();
        $this->actingAs($plain)->post(route('admin.benefits.plans.store'), [])->assertForbidden();
    }
}
