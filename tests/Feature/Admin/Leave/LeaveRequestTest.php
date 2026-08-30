<?php

namespace Tests\Feature\Admin\Leave;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
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
        $user->givePermissionTo(['leave.view', 'leave.create', 'leave.approve', 'leave.reject']);

        return $user;
    }

    public function test_submitting_a_request_does_not_touch_the_balance(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();
        $employee = Employee::factory()->for($company, 'company')->create();
        $leaveType = LeaveType::factory()->for($company, 'company')->create();

        $this->actingAs($user)->post(route('admin.leave.requests.store'), [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-03-02',
            'end_date' => '2026-03-04',
        ])->assertRedirect();

        $request = LeaveRequest::sole();
        $this->assertSame('pending', $request->status->value);
        $this->assertSame('3.00', (string) $request->days_count);
        $this->assertNull(LeaveBalance::where('employee_id', $employee->id)->first());
    }

    public function test_approval_deducts_the_balance_via_a_ledger_transaction(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();
        $employee = Employee::factory()->for($company, 'company')->create();
        $leaveType = LeaveType::factory()->for($company, 'company')->create();
        LeaveBalance::factory()->create(['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'balance' => 10]);

        $request = LeaveRequest::factory()->forEmployee($employee)->create([
            'leave_type_id' => $leaveType->id, 'start_date' => '2026-03-02', 'end_date' => '2026-03-03', 'days_count' => 2,
        ]);

        $this->actingAs($user)->put(route('admin.leave.requests.approve', $request))->assertRedirect();

        $request->refresh();
        $this->assertSame('approved', $request->status->value);
        $this->assertSame($user->id, $request->approved_by);

        $balance = LeaveBalance::where('employee_id', $employee->id)->sole();
        $this->assertSame('8.00', (string) $balance->balance);

        $transaction = $request->transactions()->sole();
        $this->assertSame('usage', $transaction->type->value);
        $this->assertSame('-2.00', (string) $transaction->amount);
    }

    public function test_cancelling_an_approved_request_reverses_the_deduction(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();
        $employee = Employee::factory()->for($company, 'company')->create();
        $leaveType = LeaveType::factory()->for($company, 'company')->create();
        LeaveBalance::factory()->create(['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'balance' => 10]);

        $request = LeaveRequest::factory()->forEmployee($employee)->create([
            'leave_type_id' => $leaveType->id, 'days_count' => 2,
        ]);
        $this->actingAs($user)->put(route('admin.leave.requests.approve', $request));

        $this->actingAs($user)->put(route('admin.leave.requests.cancel', $request))->assertRedirect();

        $request->refresh();
        $this->assertSame('cancelled', $request->status->value);

        $balance = LeaveBalance::where('employee_id', $employee->id)->sole();
        $this->assertSame('10.00', (string) $balance->balance);
        $this->assertSame(2, $request->transactions()->count());
    }

    public function test_cancelling_a_pending_request_does_not_touch_the_balance(): void
    {
        $user = $this->manager();
        $employee = Employee::factory()->create();
        $request = LeaveRequest::factory()->forEmployee($employee)->create();

        $this->actingAs($user)->put(route('admin.leave.requests.cancel', $request))->assertRedirect();

        $request->refresh();
        $this->assertSame('cancelled', $request->status->value);
        $this->assertSame(0, $request->transactions()->count());
    }

    public function test_rejecting_requires_a_reason_and_only_works_on_pending_requests(): void
    {
        $user = $this->manager();
        $request = LeaveRequest::factory()->create();

        $this->actingAs($user)->put(route('admin.leave.requests.reject', $request), [])
            ->assertSessionHasErrors('rejection_reason');

        $this->actingAs($user)->put(route('admin.leave.requests.reject', $request), [
            'rejection_reason' => 'Insufficient staffing.',
        ])->assertRedirect();

        $request->refresh();
        $this->assertSame('rejected', $request->status->value);

        $this->actingAs($user)->put(route('admin.leave.requests.reject', $request), [
            'rejection_reason' => 'Again.',
        ])->assertStatus(422);
    }

    public function test_leave_type_must_belong_to_the_employees_company(): void
    {
        $user = $this->manager();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $employee = Employee::factory()->for($companyA, 'company')->create();
        $wrongType = LeaveType::factory()->for($companyB, 'company')->create();

        $this->actingAs($user)->post(route('admin.leave.requests.store'), [
            'employee_id' => $employee->id,
            'leave_type_id' => $wrongType->id,
            'start_date' => '2026-03-02',
            'end_date' => '2026-03-03',
        ])->assertSessionHasErrors('leave_type_id');
    }

    public function test_without_permission_gets_403(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('admin.leave.requests.index'))->assertForbidden();
    }
}
