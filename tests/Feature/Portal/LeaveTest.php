<?php

namespace Tests\Feature\Portal;

use App\Domain\Leave\Services\LeaveBalanceService;
use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveTransactionType;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveTest extends TestCase
{
    use RefreshDatabase;

    private function employeeUser(): User
    {
        $employee = Employee::factory()->create();

        return User::factory()->create(['employee_id' => $employee->id]);
    }

    public function test_employee_can_submit_a_leave_request_for_themselves_only(): void
    {
        $user = $this->employeeUser();
        $leaveType = LeaveType::factory()->create(['company_id' => $user->employee->company_id]);

        $this->actingAs($user)->post(route('portal.leave.store'), [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-03',
            'reason' => 'Family trip',
        ])->assertRedirect(route('portal.leave.index'));

        $request = LeaveRequest::sole();
        $this->assertSame($user->employee_id, $request->employee_id);
        $this->assertEquals(3, $request->days_count);
        $this->assertSame(LeaveRequestStatus::Pending, $request->status);
    }

    public function test_leave_type_must_belong_to_employees_own_company(): void
    {
        $user = $this->employeeUser();
        $otherCompanyLeaveType = LeaveType::factory()->create();

        $this->actingAs($user)->post(route('portal.leave.store'), [
            'leave_type_id' => $otherCompanyLeaveType->id,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-03',
        ])->assertSessionHasErrors('leave_type_id');
    }

    public function test_employee_can_cancel_their_own_pending_request(): void
    {
        $user = $this->employeeUser();
        $request = LeaveRequest::factory()->create([
            'employee_id' => $user->employee_id,
            'status' => LeaveRequestStatus::Pending,
        ]);

        $this->actingAs($user)->put(route('portal.leave.cancel', $request))->assertRedirect();

        $this->assertSame(LeaveRequestStatus::Cancelled, $request->fresh()->status);
    }

    public function test_cancelling_an_approved_request_reverses_the_balance(): void
    {
        $user = $this->employeeUser();
        $leaveType = LeaveType::factory()->create(['company_id' => $user->employee->company_id]);
        app(LeaveBalanceService::class)->applyTransaction(
            employee: $user->employee,
            leaveType: $leaveType,
            type: LeaveTransactionType::Accrual,
            amount: 10,
            date: '2026-01-01',
        );

        $request = LeaveRequest::factory()->create([
            'employee_id' => $user->employee_id,
            'leave_type_id' => $leaveType->id,
            'days_count' => 3,
            'status' => LeaveRequestStatus::Approved,
        ]);
        app(LeaveBalanceService::class)->applyTransaction(
            employee: $user->employee,
            leaveType: $leaveType,
            type: LeaveTransactionType::Usage,
            amount: -3,
            date: '2026-01-05',
            leaveRequest: $request,
        );

        $this->actingAs($user)->put(route('portal.leave.cancel', $request))->assertRedirect();

        $this->assertSame(LeaveRequestStatus::Cancelled, $request->fresh()->status);
        $this->assertEquals(10, $user->employee->leaveBalances()->where('leave_type_id', $leaveType->id)->sole()->balance);
    }

    public function test_employee_cannot_cancel_another_employees_request(): void
    {
        $user = $this->employeeUser();
        $otherRequest = LeaveRequest::factory()->create(['status' => LeaveRequestStatus::Pending]);

        $this->actingAs($user)->put(route('portal.leave.cancel', $otherRequest))->assertNotFound();
    }

    public function test_unlinked_account_gets_a_friendly_message_and_cannot_submit(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('portal.leave.index'))
            ->assertOk()
            ->assertSee("isn't linked to an employee record", false);

        $this->actingAs($user)->get(route('portal.leave.create'))->assertNotFound();
    }
}
