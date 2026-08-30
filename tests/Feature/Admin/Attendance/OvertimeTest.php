<?php

namespace Tests\Feature\Admin\Attendance;

use App\Models\Company;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OvertimeTest extends TestCase
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
        $user->givePermissionTo(['attendance.view', 'attendance.manage']);

        return $user;
    }

    public function test_overtime_request_defaults_to_pending_and_can_be_approved(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();
        $employee = Employee::factory()->for($company, 'company')->create();

        $this->actingAs($user)->post(route('admin.attendance.overtime.store'), [
            'employee_id' => $employee->id,
            'date' => '2026-01-10',
            'requested_hours' => 2.5,
            'reason' => 'Month-end closing.',
        ])->assertRedirect();

        $overtime = OvertimeRequest::sole();
        $this->assertSame('pending', $overtime->status->value);
        $this->assertSame($user->id, $overtime->requested_by);

        $this->actingAs($user)->put(route('admin.attendance.overtime.approve', $overtime))->assertRedirect();

        $overtime->refresh();
        $this->assertSame('approved', $overtime->status->value);
        $this->assertSame($user->id, $overtime->approved_by);
        $this->assertNotNull($overtime->approved_at);
    }

    public function test_rejecting_requires_a_reason(): void
    {
        $user = $this->manager();
        $overtime = OvertimeRequest::factory()->create();

        $this->actingAs($user)->put(route('admin.attendance.overtime.reject', $overtime), [])
            ->assertSessionHasErrors('rejection_reason');

        $this->actingAs($user)->put(route('admin.attendance.overtime.reject', $overtime), [
            'rejection_reason' => 'No approval from department head.',
        ])->assertRedirect();

        $overtime->refresh();
        $this->assertSame('rejected', $overtime->status->value);
        $this->assertSame('No approval from department head.', $overtime->rejection_reason);
    }

    public function test_without_permission_gets_403(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('admin.attendance.overtime.index'))->assertForbidden();
    }
}
