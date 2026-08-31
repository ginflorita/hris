<?php

namespace Tests\Feature\Admin;

use App\Enums\CoeRequestStatus;
use App\Models\CoeRequest;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoeRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function hrUser(array $permissions = ['employees.view', 'employees.update']): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    public function test_approving_snapshots_current_employment_onto_the_request(): void
    {
        $user = $this->hrUser(['employees.view', 'employees.update', 'employees.salary.view']);
        $company = Company::factory()->create();
        $employee = Employee::factory()->for($company, 'company')->create();
        $department = Department::factory()->for($company, 'company')->create(['name' => 'Engineering']);
        $position = Position::factory()->for($company, 'company')->create(['title' => 'Software Engineer', 'department_id' => $department->id]);
        Employment::factory()->forEmployee($employee)->create([
            'position_id' => $position->id,
            'department_id' => $department->id,
            'basic_salary' => 55000,
            'effective_date' => '2023-01-15',
        ]);
        $coeRequest = CoeRequest::factory()->forEmployee($employee)->create(['type' => 'with_compensation']);

        $this->actingAs($user)->put(route('admin.coe-requests.approve', $coeRequest))->assertRedirect();

        $coeRequest->refresh();
        $this->assertSame(CoeRequestStatus::Approved, $coeRequest->status);
        $this->assertSame($user->id, $coeRequest->approved_by);
        $this->assertSame('Software Engineer', $coeRequest->snapshot_position);
        $this->assertSame('Engineering', $coeRequest->snapshot_department);
        $this->assertSame('active', $coeRequest->snapshot_employment_status);
        $this->assertSame('2023-01-15', $coeRequest->snapshot_date_hired->format('Y-m-d'));
        $this->assertSame('55000.00', $coeRequest->snapshot_monthly_salary);
    }

    public function test_approving_a_standard_request_does_not_snapshot_salary(): void
    {
        $user = $this->hrUser();
        $employee = Employee::factory()->create();
        Employment::factory()->forEmployee($employee)->create(['basic_salary' => 55000]);
        $coeRequest = CoeRequest::factory()->forEmployee($employee)->create(['type' => 'standard']);

        $this->actingAs($user)->put(route('admin.coe-requests.approve', $coeRequest))->assertRedirect();

        $this->assertNull($coeRequest->refresh()->snapshot_monthly_salary);
    }

    public function test_approving_a_with_compensation_request_requires_salary_permission(): void
    {
        $user = $this->hrUser();
        $employee = Employee::factory()->create();
        $coeRequest = CoeRequest::factory()->forEmployee($employee)->create(['type' => 'with_compensation']);

        $this->actingAs($user)->put(route('admin.coe-requests.approve', $coeRequest))->assertForbidden();

        $this->assertSame(CoeRequestStatus::Pending, $coeRequest->refresh()->status);
    }

    public function test_rejecting_requires_a_reason(): void
    {
        $user = $this->hrUser();
        $coeRequest = CoeRequest::factory()->create();

        $this->actingAs($user)->put(route('admin.coe-requests.reject', $coeRequest), [])
            ->assertSessionHasErrors('rejection_reason');

        $this->actingAs($user)->put(route('admin.coe-requests.reject', $coeRequest), [
            'rejection_reason' => 'Insufficient tenure.',
        ])->assertRedirect();

        $this->assertSame(CoeRequestStatus::Rejected, $coeRequest->refresh()->status);
    }

    public function test_only_a_pending_request_can_be_approved_or_rejected(): void
    {
        $user = $this->hrUser(['employees.view', 'employees.update', 'employees.salary.view']);
        $approved = CoeRequest::factory()->approved()->create(['type' => 'standard']);

        $this->actingAs($user)->put(route('admin.coe-requests.approve', $approved))->assertStatus(422);
        $this->actingAs($user)->put(route('admin.coe-requests.reject', $approved), ['rejection_reason' => 'test'])->assertStatus(422);
    }

    public function test_index_and_actions_require_employees_permissions(): void
    {
        $user = User::factory()->create();
        $coeRequest = CoeRequest::factory()->create();

        $this->actingAs($user)->get(route('admin.coe-requests.index'))->assertForbidden();
        $this->actingAs($user)->put(route('admin.coe-requests.approve', $coeRequest))->assertForbidden();
    }

    public function test_downloading_a_compensation_certificate_requires_salary_permission(): void
    {
        $withoutSalary = $this->hrUser();
        $withSalary = $this->hrUser(['employees.view', 'employees.update', 'employees.salary.view']);
        $coeRequest = CoeRequest::factory()->approved()->create(['type' => 'with_compensation', 'snapshot_monthly_salary' => 40000]);

        $this->actingAs($withoutSalary)->get(route('admin.coe-requests.download', $coeRequest))->assertForbidden();

        $response = $this->actingAs($withSalary)->get(route('admin.coe-requests.download', $coeRequest));
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }
}
