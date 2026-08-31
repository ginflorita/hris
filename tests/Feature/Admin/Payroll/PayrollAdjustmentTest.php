<?php

namespace Tests\Feature\Admin\Payroll;

use App\Domain\Payroll\Services\PayrollCalculationService;
use App\Enums\EmploymentStatus;
use App\Enums\PayrollPeriodStatus;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\PayrollGroup;
use App\Models\PayrollItem;
use App\Models\PayrollItemLine;
use App\Models\PayrollPeriod;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function payrollAdmin(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['payroll.view', 'payroll.create', 'payroll.process']);

        return $user;
    }

    private function processedItem(): PayrollItem
    {
        $company = Company::factory()->create();
        $group = PayrollGroup::factory()->for($company, 'company')->create(['pay_frequency' => 'monthly']);
        $period = PayrollPeriod::factory()->for($company, 'company')->for($group, 'payrollGroup')->create([
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'pay_date' => '2026-02-05',
            'status' => PayrollPeriodStatus::Draft,
        ]);

        $employee = Employee::factory()->for($company, 'company')->create();
        Employment::factory()->for($employee, 'employee')->for($company, 'company')->create([
            'payroll_group_id' => $group->id,
            'basic_salary' => 30000,
            'status' => EmploymentStatus::Active,
            'effective_date' => '2025-01-01',
            'end_date' => null,
        ]);

        app(PayrollCalculationService::class)->process($period);

        return PayrollItem::where('payroll_period_id', $period->id)->sole();
    }

    public function test_adding_and_removing_an_adjustment_updates_totals_but_not_contributions_or_tax(): void
    {
        $user = $this->payrollAdmin();
        $item = $this->processedItem();
        $originalContributions = $item->total_employee_contributions;
        $originalTax = $item->tax_amount;

        $this->actingAs($user)->post(route('admin.payroll.payroll-items.adjustments.store', $item), [
            'type' => 'deduction',
            'label' => 'Loan repayment',
            'amount' => 500,
            'remarks' => 'Employee loan installment 3/12',
        ])->assertRedirect();

        $item->refresh();
        $this->assertEquals(500, $item->total_deductions);
        $this->assertEquals(30000, $item->gross_earnings); // no compensation items in this scenario
        $this->assertEquals($originalContributions, $item->total_employee_contributions);
        $this->assertEquals($originalTax, $item->tax_amount);
        $this->assertEquals(30000 - 500 - (float) $originalContributions - (float) $originalTax, (float) $item->net_pay);

        $line = $item->lines()->where('is_adjustment', true)->sole();
        $this->assertTrue($line->is_adjustment);
        $this->assertSame($user->id, $line->created_by);

        $this->actingAs($user)->delete(route('admin.payroll.payroll-items.adjustments.destroy', [$item, $line]))
            ->assertRedirect();

        $item->refresh();
        $this->assertEquals(0, $item->total_deductions);
        $this->assertEquals(30000 - (float) $originalContributions - (float) $originalTax, (float) $item->net_pay);
    }

    public function test_earning_adjustment_increases_gross_and_net(): void
    {
        $user = $this->payrollAdmin();
        $item = $this->processedItem();

        $this->actingAs($user)->post(route('admin.payroll.payroll-items.adjustments.store', $item), [
            'type' => 'earning',
            'label' => 'Performance bonus',
            'amount' => 1000,
            'remarks' => 'One-off bonus approved by manager',
        ])->assertRedirect();

        $item->refresh();
        $this->assertEquals(31000, $item->gross_earnings);
    }

    public function test_adjustment_survives_reprocessing(): void
    {
        $user = $this->payrollAdmin();
        $item = $this->processedItem();

        $this->actingAs($user)->post(route('admin.payroll.payroll-items.adjustments.store', $item), [
            'type' => 'deduction',
            'label' => 'Loan repayment',
            'amount' => 500,
            'remarks' => 'Employee loan installment 3/12',
        ]);

        $period = $item->payrollPeriod;
        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.process', $period))->assertRedirect();

        $reprocessed = PayrollItem::sole();
        $this->assertEquals(500, $reprocessed->total_deductions);
        $adjustmentLine = $reprocessed->lines()->where('is_adjustment', true)->sole();
        $this->assertSame('Loan repayment', $adjustmentLine->label);
    }

    public function test_adjustments_are_blocked_once_period_is_past_review(): void
    {
        $user = $this->payrollAdmin();
        $item = $this->processedItem();
        $item->payrollPeriod->update(['status' => PayrollPeriodStatus::Approved]);

        $this->actingAs($user)->post(route('admin.payroll.payroll-items.adjustments.store', $item), [
            'type' => 'deduction',
            'label' => 'Late adjustment',
            'amount' => 100,
            'remarks' => 'Should be rejected',
        ])->assertSessionHasErrors('payrollItem');

        $this->assertSame(0, PayrollItemLine::where('is_adjustment', true)->count());
    }

    public function test_adjustment_from_another_item_cannot_be_removed(): void
    {
        $user = $this->payrollAdmin();
        $itemA = $this->processedItem();
        $itemB = $this->processedItem();

        $this->actingAs($user)->post(route('admin.payroll.payroll-items.adjustments.store', $itemA), [
            'type' => 'deduction', 'label' => 'X', 'amount' => 100, 'remarks' => 'test',
        ]);
        $line = $itemA->lines()->where('is_adjustment', true)->sole();

        $this->actingAs($user)->delete(route('admin.payroll.payroll-items.adjustments.destroy', [$itemB, $line]))
            ->assertNotFound();
    }

    public function test_negative_net_pay_is_flagged_as_a_validation_issue(): void
    {
        $item = $this->processedItem();
        $item->update(['net_pay' => -50]);

        $this->assertNotEmpty($item->validationIssues());
        $this->assertStringContainsString('negative', $item->validationIssues()[0]);
    }

    public function test_missing_tax_table_is_flagged_as_a_validation_issue(): void
    {
        $item = $this->processedItem();
        $item->update(['tax_table_id' => null]);

        $issues = $item->validationIssues();
        $this->assertNotEmpty($issues);
        $this->assertStringContainsString('tax table', $issues[array_key_last($issues)]);
    }

    public function test_period_show_page_surfaces_the_issue_badge(): void
    {
        $user = $this->payrollAdmin();
        $item = $this->processedItem();
        $item->update(['net_pay' => -50]);

        $this->actingAs($user)->get(route('admin.payroll.payroll-periods.show', $item->payrollPeriod))
            ->assertOk()
            ->assertSee('issue');
    }
}
