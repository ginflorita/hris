<?php

namespace Tests\Feature\Portal;

use App\Enums\CoeRequestStatus;
use App\Models\CoeRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoeRequestTest extends TestCase
{
    use RefreshDatabase;

    private function employeeUser(): User
    {
        $employee = Employee::factory()->create();

        return User::factory()->create(['employee_id' => $employee->id]);
    }

    public function test_employee_can_submit_a_coe_request_for_themselves_only(): void
    {
        $user = $this->employeeUser();

        $this->actingAs($user)->post(route('portal.coe.store'), [
            'type' => 'employment_verification',
            'purpose' => 'Bank loan application',
        ])->assertRedirect();

        $request = CoeRequest::sole();
        $this->assertSame($user->employee_id, $request->employee_id);
        $this->assertSame($user->employee->company_id, $request->company_id);
        $this->assertSame($user->id, $request->requested_by);
        $this->assertSame(CoeRequestStatus::Pending, $request->status);
        $this->assertNull($request->snapshot_position);
    }

    public function test_unlinked_account_cannot_submit(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('portal.coe.store'), [
            'type' => 'standard',
        ])->assertNotFound();
    }

    public function test_employee_sees_only_their_own_requests(): void
    {
        $user = $this->employeeUser();
        CoeRequest::factory()->forEmployee($user->employee)->create(['purpose' => 'Mine']);
        CoeRequest::factory()->create(['purpose' => 'Someone elses']);

        $response = $this->actingAs($user)->get(route('portal.coe.index'));

        $response->assertOk()->assertSee('Mine')->assertDontSee('Someone elses');
    }

    public function test_employee_can_download_their_own_approved_certificate(): void
    {
        $user = $this->employeeUser();
        $coeRequest = CoeRequest::factory()->forEmployee($user->employee)->approved()->create();

        $response = $this->actingAs($user)->get(route('portal.coe.download', $coeRequest));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_employee_cannot_download_a_pending_or_another_employees_certificate(): void
    {
        $user = $this->employeeUser();
        $ownPending = CoeRequest::factory()->forEmployee($user->employee)->create();
        $othersApproved = CoeRequest::factory()->approved()->create();

        $this->actingAs($user)->get(route('portal.coe.download', $ownPending))->assertNotFound();
        $this->actingAs($user)->get(route('portal.coe.download', $othersApproved))->assertNotFound();
    }
}
