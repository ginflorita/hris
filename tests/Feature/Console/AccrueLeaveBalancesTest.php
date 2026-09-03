<?php

namespace Tests\Feature\Console;

use App\Enums\AccrualFrequency;
use App\Enums\LeaveTransactionType;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeavePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccrueLeaveBalancesTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_policy_accrues_on_the_first_of_the_month(): void
    {
        $this->travelTo('2026-03-01');
        $policy = LeavePolicy::factory()->create(['accrual_frequency' => AccrualFrequency::Monthly, 'accrual_rate' => 1.25]);
        $employee = Employee::factory()->for($policy->company, 'company')->create();

        $this->artisan('leave:accrue')->assertSuccessful();

        $balance = LeaveBalance::where('employee_id', $employee->id)->where('leave_type_id', $policy->leave_type_id)->sole();
        $this->assertSame('1.25', (string) $balance->balance);

        $transaction = $balance->employee->leaveTransactions()->sole();
        $this->assertSame(LeaveTransactionType::Accrual, $transaction->type);
    }

    public function test_monthly_policy_does_not_accrue_on_other_days(): void
    {
        $this->travelTo('2026-03-15');
        $policy = LeavePolicy::factory()->create(['accrual_frequency' => AccrualFrequency::Monthly]);
        Employee::factory()->for($policy->company, 'company')->create();

        $this->artisan('leave:accrue')->assertSuccessful();

        $this->assertSame(0, LeaveBalance::count());
    }

    public function test_annual_policy_only_accrues_on_january_first(): void
    {
        $policy = LeavePolicy::factory()->create(['accrual_frequency' => AccrualFrequency::Annually, 'accrual_rate' => 15]);
        $employee = Employee::factory()->for($policy->company, 'company')->create();

        $this->travelTo('2026-06-01');
        $this->artisan('leave:accrue')->assertSuccessful();
        $this->assertSame(0, LeaveBalance::count());

        $this->travelTo('2027-01-01');
        $this->artisan('leave:accrue')->assertSuccessful();
        $balance = LeaveBalance::where('employee_id', $employee->id)->sole();
        $this->assertSame('15.00', (string) $balance->balance);
    }

    public function test_accrual_is_capped_at_the_policy_max_balance(): void
    {
        $this->travelTo('2026-03-01');
        $policy = LeavePolicy::factory()->create([
            'accrual_frequency' => AccrualFrequency::Monthly,
            'accrual_rate' => 5,
            'max_balance' => 12,
        ]);
        $employee = Employee::factory()->for($policy->company, 'company')->create();
        LeaveBalance::create(['employee_id' => $employee->id, 'leave_type_id' => $policy->leave_type_id, 'balance' => 10]);

        $this->artisan('leave:accrue')->assertSuccessful();

        $balance = LeaveBalance::where('employee_id', $employee->id)->sole();
        $this->assertSame('12.00', (string) $balance->balance);
    }

    public function test_a_balance_already_at_the_cap_is_left_untouched(): void
    {
        $this->travelTo('2026-03-01');
        $policy = LeavePolicy::factory()->create([
            'accrual_frequency' => AccrualFrequency::Monthly,
            'max_balance' => 12,
        ]);
        $employee = Employee::factory()->for($policy->company, 'company')->create();
        LeaveBalance::create(['employee_id' => $employee->id, 'leave_type_id' => $policy->leave_type_id, 'balance' => 12]);

        $this->artisan('leave:accrue')->assertSuccessful();

        $this->assertSame(0, $employee->leaveTransactions()->count());
    }

    public function test_an_inactive_policy_and_an_archived_employee_are_both_skipped(): void
    {
        $this->travelTo('2026-03-01');
        $inactivePolicy = LeavePolicy::factory()->create(['accrual_frequency' => AccrualFrequency::Monthly, 'is_active' => false]);
        Employee::factory()->for($inactivePolicy->company, 'company')->create();

        $activePolicy = LeavePolicy::factory()->create(['accrual_frequency' => AccrualFrequency::Monthly]);
        Employee::factory()->for($activePolicy->company, 'company')->create(['archived_at' => now()]);

        $this->artisan('leave:accrue')->assertSuccessful();

        $this->assertSame(0, LeaveBalance::count());
    }
}
