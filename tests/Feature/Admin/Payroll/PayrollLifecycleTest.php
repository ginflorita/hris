<?php

namespace Tests\Feature\Admin\Payroll;

use App\Enums\PayrollPeriodStatus;
use App\Models\PayrollPeriod;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function payrollAdmin(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['payroll.view', 'payroll.create', 'payroll.process', 'payroll.approve', 'payroll.finalize', 'payroll.lock']);

        return $user;
    }

    public function test_full_lifecycle_from_for_review_to_published(): void
    {
        $user = $this->payrollAdmin();
        $period = PayrollPeriod::factory()->create(['status' => PayrollPeriodStatus::ForReview]);

        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.submit-for-approval', $period))->assertRedirect();
        $period->refresh();
        $this->assertSame(PayrollPeriodStatus::ForApproval, $period->status);
        $this->assertSame($user->id, $period->submitted_by);

        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.approve', $period))->assertRedirect();
        $period->refresh();
        $this->assertSame(PayrollPeriodStatus::Approved, $period->status);
        $this->assertSame($user->id, $period->approved_by);

        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.finalize', $period))->assertRedirect();
        $period->refresh();
        $this->assertSame(PayrollPeriodStatus::Finalized, $period->status);
        $this->assertNotNull($period->finalized_at);

        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.lock', $period))->assertRedirect();
        $period->refresh();
        $this->assertSame(PayrollPeriodStatus::Locked, $period->status);

        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.publish', $period))->assertRedirect();
        $period->refresh();
        $this->assertSame(PayrollPeriodStatus::Published, $period->status);
        $this->assertSame($user->id, $period->published_by);
    }

    public function test_rejecting_sends_the_period_back_to_for_review_with_a_reason(): void
    {
        $user = $this->payrollAdmin();
        $period = PayrollPeriod::factory()->create(['status' => PayrollPeriodStatus::ForApproval]);

        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.reject', $period), [
            'rejection_reason' => 'Missing overtime for two employees',
        ])->assertRedirect();

        $period->refresh();
        $this->assertSame(PayrollPeriodStatus::ForReview, $period->status);
        $this->assertSame('Missing overtime for two employees', $period->rejection_reason);
    }

    public function test_reject_requires_a_reason(): void
    {
        $user = $this->payrollAdmin();
        $period = PayrollPeriod::factory()->create(['status' => PayrollPeriodStatus::ForApproval]);

        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.reject', $period), [])
            ->assertSessionHasErrors('rejection_reason');

        $this->assertSame(PayrollPeriodStatus::ForApproval, $period->fresh()->status);
    }

    public function test_transitions_are_blocked_out_of_order(): void
    {
        $user = $this->payrollAdmin();
        $draft = PayrollPeriod::factory()->create(['status' => PayrollPeriodStatus::Draft]);

        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.approve', $draft))->assertStatus(422);
        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.finalize', $draft))->assertStatus(422);
        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.lock', $draft))->assertStatus(422);
        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.publish', $draft))->assertStatus(422);

        $this->assertSame(PayrollPeriodStatus::Draft, $draft->fresh()->status);
    }

    public function test_approve_requires_payroll_approve_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['payroll.view', 'payroll.create', 'payroll.process']);
        $period = PayrollPeriod::factory()->create(['status' => PayrollPeriodStatus::ForApproval]);

        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.approve', $period))->assertForbidden();
    }

    public function test_finalize_requires_payroll_finalize_permission_not_just_approve(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['payroll.view', 'payroll.create', 'payroll.process', 'payroll.approve']);
        $period = PayrollPeriod::factory()->create(['status' => PayrollPeriodStatus::Approved]);

        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.finalize', $period))->assertForbidden();
    }

    public function test_finalized_period_cannot_be_reprocessed(): void
    {
        $user = $this->payrollAdmin();
        $period = PayrollPeriod::factory()->create(['status' => PayrollPeriodStatus::Finalized]);

        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.process', $period))
            ->assertSessionHasErrors('payrollPeriod');
    }
}
