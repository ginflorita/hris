<?php

namespace Tests\Feature\Admin\Payroll;

use App\Enums\PayrollPeriodStatus;
use App\Models\Company;
use App\Models\PayrollGroup;
use App\Models\PayrollPeriod;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollPeriodTest extends TestCase
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
        $user->givePermissionTo(['payroll.view', 'payroll.create']);

        return $user;
    }

    public function test_payroll_group_crud_and_code_uniqueness_per_company(): void
    {
        $user = $this->payrollAdmin();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $this->actingAs($user)->post(route('admin.payroll.payroll-groups.store'), [
            'company_id' => $companyA->id,
            'name' => 'Monthly - Head Office',
            'code' => 'MHO',
            'pay_frequency' => 'monthly',
        ])->assertRedirect(route('admin.payroll.payroll-groups.index'));

        $group = PayrollGroup::sole();
        $this->assertTrue($group->is_active);

        // Same code, different company: allowed.
        $this->actingAs($user)->post(route('admin.payroll.payroll-groups.store'), [
            'company_id' => $companyB->id,
            'name' => 'Monthly - Head Office',
            'code' => 'MHO',
            'pay_frequency' => 'monthly',
        ])->assertRedirect(route('admin.payroll.payroll-groups.index'));

        // Same code, same company: rejected.
        $this->actingAs($user)->post(route('admin.payroll.payroll-groups.store'), [
            'company_id' => $companyA->id,
            'name' => 'Duplicate',
            'code' => 'MHO',
            'pay_frequency' => 'weekly',
        ])->assertSessionHasErrors('code');
    }

    public function test_payroll_group_cannot_be_deleted_while_it_has_periods(): void
    {
        $user = $this->payrollAdmin();
        $group = PayrollGroup::factory()->create();
        PayrollPeriod::factory()->for($group->company, 'company')->for($group, 'payrollGroup')->create();

        $this->actingAs($user)->delete(route('admin.payroll.payroll-groups.destroy', $group))
            ->assertSessionHasErrors('payrollGroup');
    }

    public function test_payroll_period_crud_and_overlap_rejection(): void
    {
        $user = $this->payrollAdmin();
        $company = Company::factory()->create();
        $group = PayrollGroup::factory()->for($company, 'company')->create();

        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.store'), [
            'company_id' => $company->id,
            'payroll_group_id' => $group->id,
            'name' => 'January 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'pay_date' => '2026-02-05',
        ])->assertRedirect(route('admin.payroll.payroll-periods.index'));

        $period = PayrollPeriod::sole();
        $this->assertSame(PayrollPeriodStatus::Draft, $period->status);

        // Overlapping range in the same group is rejected.
        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.store'), [
            'company_id' => $company->id,
            'payroll_group_id' => $group->id,
            'name' => 'Overlap',
            'start_date' => '2026-01-15',
            'end_date' => '2026-02-15',
            'pay_date' => '2026-02-20',
        ])->assertSessionHasErrors('start_date');

        // Non-overlapping range is accepted.
        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.store'), [
            'company_id' => $company->id,
            'payroll_group_id' => $group->id,
            'name' => 'February 2026',
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-28',
            'pay_date' => '2026-03-05',
        ])->assertRedirect(route('admin.payroll.payroll-periods.index'));

        $this->assertSame(2, PayrollPeriod::count());
    }

    public function test_payroll_period_group_must_belong_to_the_same_company(): void
    {
        $user = $this->payrollAdmin();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $group = PayrollGroup::factory()->for($companyA, 'company')->create();

        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.store'), [
            'company_id' => $companyB->id,
            'payroll_group_id' => $group->id,
            'name' => 'January 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'pay_date' => '2026-02-05',
        ])->assertSessionHasErrors('payroll_group_id');
    }

    public function test_non_draft_period_cannot_be_edited_or_deleted(): void
    {
        $user = $this->payrollAdmin();
        $period = PayrollPeriod::factory()->create(['status' => PayrollPeriodStatus::Processing]);

        $this->actingAs($user)->put(route('admin.payroll.payroll-periods.update', $period), [
            'company_id' => $period->company_id,
            'payroll_group_id' => $period->payroll_group_id,
            'name' => 'Changed',
            'start_date' => $period->start_date->format('Y-m-d'),
            'end_date' => $period->end_date->format('Y-m-d'),
            'pay_date' => $period->pay_date->format('Y-m-d'),
        ])->assertSessionHasErrors('payrollPeriod');
        $this->assertSame('January 2026', $period->fresh()->name);

        $this->actingAs($user)->delete(route('admin.payroll.payroll-periods.destroy', $period))
            ->assertSessionHasErrors('payrollPeriod');
        $this->assertModelExists($period);
    }

    public function test_without_permission_gets_403(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('admin.payroll.payroll-groups.index'))->assertForbidden();
        $this->actingAs($plain)->get(route('admin.payroll.payroll-periods.index'))->assertForbidden();
    }
}
