<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\TrainingCourse;
use App\Models\TrainingProvider;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingProviderTest extends TestCase
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

    public function test_creating_a_provider(): void
    {
        $user = $this->officer();
        $company = Company::factory()->create();

        $this->actingAs($user)->post(route('admin.training.providers.store'), [
            'company_id' => $company->id,
            'name' => 'Acme Learning',
            'contact_email' => 'training@acme.test',
        ])->assertRedirect();

        $provider = TrainingProvider::sole();
        $this->assertSame('Acme Learning', $provider->name);
        $this->assertTrue($provider->is_active);
    }

    public function test_name_is_unique_per_company_only(): void
    {
        $user = $this->officer();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        TrainingProvider::factory()->create(['company_id' => $companyA->id, 'name' => 'Acme Learning']);

        $this->actingAs($user)->post(route('admin.training.providers.store'), [
            'company_id' => $companyA->id,
            'name' => 'Acme Learning',
        ])->assertSessionHasErrors('name');

        $this->actingAs($user)->post(route('admin.training.providers.store'), [
            'company_id' => $companyB->id,
            'name' => 'Acme Learning',
        ])->assertRedirect()->assertSessionDoesntHaveErrors();
    }

    public function test_cannot_delete_a_provider_with_courses(): void
    {
        $user = $this->officer();
        $provider = TrainingProvider::factory()->create();
        TrainingCourse::factory()->create(['training_provider_id' => $provider->id, 'company_id' => $provider->company_id]);

        $this->actingAs($user)->delete(route('admin.training.providers.destroy', $provider))
            ->assertRedirect()
            ->assertSessionHasErrors('provider');

        $this->assertNotNull($provider->fresh());
    }

    public function test_requires_permission(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('admin.training.providers.index'))->assertForbidden();
        $this->actingAs($plain)->post(route('admin.training.providers.store'), [])->assertForbidden();
    }
}
