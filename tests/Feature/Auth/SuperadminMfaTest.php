<?php

namespace Tests\Feature\Auth;

use App\Enums\DefaultRole;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperadminMfaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_superadmin_without_two_factor_is_redirected_to_security_from_the_dashboard(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole(DefaultRole::Superadmin->value);

        $this->actingAs($superadmin)->get('/')->assertRedirect(route('security.index'));
    }

    public function test_superadmin_without_two_factor_can_still_reach_the_security_page(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole(DefaultRole::Superadmin->value);

        $this->actingAs($superadmin)->get(route('security.index'))->assertOk();
    }

    public function test_superadmin_without_two_factor_can_still_log_out(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole(DefaultRole::Superadmin->value);

        $this->actingAs($superadmin)->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_superadmin_with_two_factor_confirmed_reaches_the_dashboard_normally(): void
    {
        $superadmin = User::factory()->create([
            'two_factor_secret' => encrypt('placeholder-secret'),
            'two_factor_confirmed_at' => now(),
        ]);
        $superadmin->assignRole(DefaultRole::Superadmin->value);

        $this->actingAs($superadmin)->get('/')->assertOk();
    }

    public function test_non_superadmin_is_never_redirected_for_missing_two_factor(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertOk();
    }
}
