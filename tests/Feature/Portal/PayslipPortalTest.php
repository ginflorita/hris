<?php

namespace Tests\Feature\Portal;

use App\Enums\PayrollPeriodStatus;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\PayslipAccessLog;
use App\Models\User;
use App\Notifications\PayslipPublished;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PayslipPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function employeeUser(): User
    {
        $employee = Employee::factory()->create();

        return User::factory()->create(['employee_id' => $employee->id]);
    }

    public function test_unlinked_account_sees_a_friendly_message_instead_of_an_empty_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('portal.payslips.index'))
            ->assertOk()
            ->assertSee("isn't linked to an employee record", false);
    }

    public function test_employee_sees_only_their_own_published_payslips(): void
    {
        $user = $this->employeeUser();
        $published = PayrollItem::factory()->create(['employee_id' => $user->employee_id]);
        $published->payrollPeriod->update(['status' => PayrollPeriodStatus::Published, 'name' => 'Published Period']);

        $notYetPublished = PayrollItem::factory()->create(['employee_id' => $user->employee_id]);
        $notYetPublished->payrollPeriod->update(['status' => PayrollPeriodStatus::Finalized, 'name' => 'Finalized Period']);

        $someoneElse = PayrollItem::factory()->create();
        $someoneElse->payrollPeriod->update(['status' => PayrollPeriodStatus::Published, 'name' => 'Someone Elses Period']);

        $response = $this->actingAs($user)->get(route('portal.payslips.index'));

        $response->assertOk()
            ->assertSee($published->payrollPeriod->name)
            ->assertDontSee($notYetPublished->payrollPeriod->name)
            ->assertDontSee($someoneElse->payrollPeriod->name);
    }

    public function test_viewing_an_own_published_payslip_logs_access(): void
    {
        $user = $this->employeeUser();
        $item = PayrollItem::factory()->create(['employee_id' => $user->employee_id]);
        $item->payrollPeriod->update(['status' => PayrollPeriodStatus::Published]);

        $this->actingAs($user)->get(route('portal.payslips.show', $item))->assertOk();

        $log = PayslipAccessLog::sole();
        $this->assertSame($item->id, $log->payroll_item_id);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('viewed', $log->action->value);
    }

    public function test_downloading_logs_access_and_returns_a_pdf(): void
    {
        $user = $this->employeeUser();
        $item = PayrollItem::factory()->create(['employee_id' => $user->employee_id]);
        $item->payrollPeriod->update(['status' => PayrollPeriodStatus::Published]);
        $item->lines()->create(['type' => 'earning', 'category' => 'basic_salary', 'label' => 'Basic Pay', 'amount' => $item->basic_salary]);

        $response = $this->actingAs($user)->get(route('portal.payslips.download', $item));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertSame('downloaded', PayslipAccessLog::sole()->action->value);
    }

    public function test_cannot_view_another_employees_payslip(): void
    {
        $user = $this->employeeUser();
        $someoneElse = PayrollItem::factory()->create();
        $someoneElse->payrollPeriod->update(['status' => PayrollPeriodStatus::Published]);

        $this->actingAs($user)->get(route('portal.payslips.show', $someoneElse))->assertNotFound();
        $this->actingAs($user)->get(route('portal.payslips.download', $someoneElse))->assertNotFound();
        $this->assertSame(0, PayslipAccessLog::count());
    }

    public function test_cannot_view_own_payslip_before_it_is_published(): void
    {
        $user = $this->employeeUser();
        $item = PayrollItem::factory()->create(['employee_id' => $user->employee_id]);
        $item->payrollPeriod->update(['status' => PayrollPeriodStatus::Finalized]);

        $this->actingAs($user)->get(route('portal.payslips.show', $item))->assertNotFound();
    }

    public function test_admin_payroll_permissions_do_not_bypass_portal_ownership(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(['payroll.view', 'payroll.export']);
        $item = PayrollItem::factory()->create();
        $item->payrollPeriod->update(['status' => PayrollPeriodStatus::Published]);

        $this->actingAs($admin)->get(route('portal.payslips.show', $item))->assertNotFound();
    }

    public function test_publishing_a_period_notifies_linked_employees(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->givePermissionTo(['payroll.lock']);

        $employeeUser = $this->employeeUser();
        $period = PayrollPeriod::factory()->create(['status' => PayrollPeriodStatus::Locked]);
        $item = PayrollItem::factory()->create(['payroll_period_id' => $period->id, 'employee_id' => $employeeUser->employee_id]);

        $this->actingAs($admin)->post(route('admin.payroll.payroll-periods.publish', $period))->assertRedirect();

        Notification::assertSentTo($employeeUser, PayslipPublished::class);
    }
}
