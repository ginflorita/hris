<?php

namespace Tests\Feature\Console;

use App\Enums\LeaveTransactionType;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeavePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarryOverLeaveBalancesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_balance_above_the_carry_over_limit_is_capped_and_the_excess_forfeited(): void
    {
        $policy = LeavePolicy::factory()->create(['carry_over_days' => 5]);
        $employee = Employee::factory()->for($policy->company, 'company')->create();
        $balance = LeaveBalance::create(['employee_id' => $employee->id, 'leave_type_id' => $policy->leave_type_id, 'balance' => 8]);

        $this->artisan('leave:carry-over')->assertSuccessful();

        $this->assertSame('5.00', (string) $balance->refresh()->balance);
        $transaction = $employee->leaveTransactions()->sole();
        $this->assertSame(LeaveTransactionType::CarryOver, $transaction->type);
        $this->assertSame('-3.00', (string) $transaction->amount);
    }

    public function test_a_balance_at_or_below_the_limit_is_left_untouched(): void
    {
        $policy = LeavePolicy::factory()->create(['carry_over_days' => 5]);
        $employee = Employee::factory()->for($policy->company, 'company')->create();
        LeaveBalance::create(['employee_id' => $employee->id, 'leave_type_id' => $policy->leave_type_id, 'balance' => 5]);

        $this->artisan('leave:carry-over')->assertSuccessful();

        $this->assertSame(0, $employee->leaveTransactions()->count());
    }

    public function test_a_policy_with_no_carry_over_limit_is_skipped(): void
    {
        $policy = LeavePolicy::factory()->create(['carry_over_days' => null]);
        $employee = Employee::factory()->for($policy->company, 'company')->create();
        LeaveBalance::create(['employee_id' => $employee->id, 'leave_type_id' => $policy->leave_type_id, 'balance' => 100]);

        $this->artisan('leave:carry-over')->assertSuccessful();

        $this->assertSame(0, $employee->leaveTransactions()->count());
    }
}
