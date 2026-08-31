<?php

namespace Tests\Feature\Portal;

use App\Enums\OvertimeStatus;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OvertimeTest extends TestCase
{
    use RefreshDatabase;

    private function employeeUser(): User
    {
        $employee = Employee::factory()->create();

        return User::factory()->create(['employee_id' => $employee->id]);
    }

    public function test_employee_can_submit_an_overtime_request_for_themselves_only(): void
    {
        $user = $this->employeeUser();

        $this->actingAs($user)->post(route('portal.overtime.store'), [
            'date' => '2026-03-05',
            'requested_hours' => 2.5,
            'reason' => 'Month-end close',
        ])->assertRedirect(route('portal.overtime.index'));

        $request = OvertimeRequest::sole();
        $this->assertSame($user->employee_id, $request->employee_id);
        $this->assertSame($user->employee->company_id, $request->company_id);
        $this->assertSame($user->id, $request->requested_by);
        $this->assertSame(OvertimeStatus::Pending, $request->status);
    }

    public function test_employee_sees_only_their_own_overtime_requests(): void
    {
        $user = $this->employeeUser();
        OvertimeRequest::factory()->create(['employee_id' => $user->employee_id, 'reason' => 'Mine']);
        OvertimeRequest::factory()->create(['reason' => 'Someone elses']);

        $response = $this->actingAs($user)->get(route('portal.overtime.index'));

        $response->assertOk()->assertSee('Mine')->assertDontSee('Someone elses');
    }

    public function test_unlinked_account_cannot_submit(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('portal.overtime.store'), [
            'date' => '2026-03-05',
            'requested_hours' => 2,
            'reason' => 'test',
        ])->assertNotFound();
    }
}
