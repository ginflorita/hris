<?php

namespace Tests\Feature\Admin;

use App\Enums\PerformanceCycleStatus;
use App\Models\Company;
use App\Models\PerformanceCycle;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceCycleTest extends TestCase
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

    public function test_creating_a_cycle_starts_as_draft(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();

        $this->actingAs($user)->post(route('admin.performance.cycles.store'), [
            'company_id' => $company->id,
            'name' => '2026 Annual Review',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ])->assertRedirect();

        $cycle = PerformanceCycle::sole();
        $this->assertSame(PerformanceCycleStatus::Draft, $cycle->status);
    }

    public function test_activate_and_close_follow_the_draft_active_closed_lifecycle(): void
    {
        $user = $this->manager();
        $cycle = PerformanceCycle::factory()->create();

        $this->actingAs($user)->put(route('admin.performance.cycles.close', $cycle))->assertStatus(422);

        $this->actingAs($user)->put(route('admin.performance.cycles.activate', $cycle))->assertRedirect();
        $this->assertSame(PerformanceCycleStatus::Active, $cycle->refresh()->status);

        $this->actingAs($user)->put(route('admin.performance.cycles.activate', $cycle))->assertStatus(422);

        $this->actingAs($user)->put(route('admin.performance.cycles.close', $cycle))->assertRedirect();
        $this->assertSame(PerformanceCycleStatus::Closed, $cycle->refresh()->status);
    }

    public function test_requires_permission(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('admin.performance.cycles.index'))->assertForbidden();
        $this->actingAs($plain)->post(route('admin.performance.cycles.store'), [])->assertForbidden();
    }
}
