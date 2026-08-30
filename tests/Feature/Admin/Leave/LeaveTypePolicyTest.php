<?php

namespace Tests\Feature\Admin\Leave;

use App\Models\Company;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveTypePolicyTest extends TestCase
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
        $user->givePermissionTo(['leave.view', 'leave.create']);

        return $user;
    }

    public function test_leave_type_crud_and_unchecking_boxes_persists(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();

        $this->actingAs($user)->post(route('admin.leave.types.store'), [
            'company_id' => $company->id,
            'name' => 'Vacation Leave',
            'code' => 'VL',
            'max_days_per_year' => 15,
        ])->assertRedirect(route('admin.leave.types.index'));

        $leaveType = LeaveType::sole();
        $this->assertTrue($leaveType->is_paid);
        $this->assertTrue($leaveType->requires_approval);
        $this->assertTrue($leaveType->is_active);

        $this->actingAs($user)->put(route('admin.leave.types.update', $leaveType), [
            'company_id' => $company->id,
            'name' => 'Vacation Leave',
            'code' => 'VL',
            // is_paid, requires_approval, is_active all omitted -> unchecked
        ])->assertRedirect(route('admin.leave.types.index'));

        $leaveType->refresh();
        $this->assertFalse($leaveType->is_paid);
        $this->assertFalse($leaveType->requires_approval);
        $this->assertFalse($leaveType->is_active);
    }

    public function test_leave_type_cannot_be_deleted_while_a_policy_references_it(): void
    {
        $user = $this->manager();
        $company = Company::factory()->create();
        $leaveType = LeaveType::factory()->for($company, 'company')->create();
        LeavePolicy::factory()->for($company, 'company')->create(['leave_type_id' => $leaveType->id]);

        $this->actingAs($user)->delete(route('admin.leave.types.destroy', $leaveType))
            ->assertSessionHasErrors('leaveType');
    }

    public function test_leave_policy_type_must_belong_to_the_same_company(): void
    {
        $user = $this->manager();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $leaveType = LeaveType::factory()->for($companyA, 'company')->create();

        $this->actingAs($user)->post(route('admin.leave.policies.store'), [
            'company_id' => $companyB->id,
            'leave_type_id' => $leaveType->id,
            'name' => 'Standard',
            'accrual_rate' => 1.25,
            'accrual_frequency' => 'monthly',
        ])->assertSessionHasErrors('leave_type_id');

        $this->actingAs($user)->post(route('admin.leave.policies.store'), [
            'company_id' => $companyA->id,
            'leave_type_id' => $leaveType->id,
            'name' => 'Standard',
            'accrual_rate' => 1.25,
            'accrual_frequency' => 'monthly',
            'max_balance' => 30,
        ])->assertRedirect(route('admin.leave.policies.index'));

        $policy = LeavePolicy::sole();
        $this->assertSame($leaveType->id, $policy->leave_type_id);
    }

    public function test_without_permission_gets_403(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('admin.leave.types.index'))->assertForbidden();
        $this->actingAs($plain)->get(route('admin.leave.policies.index'))->assertForbidden();
    }
}
