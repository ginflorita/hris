<?php

namespace Tests\Feature\Admin\Payroll;

use App\Enums\CompensationFrequency;
use App\Enums\CompensationItemType;
use App\Enums\EmploymentStatus;
use App\Enums\PayrollPeriodStatus;
use App\Models\Company;
use App\Models\CompensationItem;
use App\Models\ContributionRateBracket;
use App\Models\ContributionRateTable;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\PayrollGroup;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\TaxTable;
use App\Models\TaxTableBracket;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollCalculationTest extends TestCase
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

    private function setUpScenario(): array
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

        CompensationItem::factory()->for($employee, 'employee')->create([
            'company_id' => $company->id,
            'type' => CompensationItemType::Allowance,
            'name' => 'Transport Allowance',
            'amount' => 2000,
            'frequency' => CompensationFrequency::Monthly,
            'effective_date' => '2025-06-01',
            'end_date' => null,
            'is_active' => true,
        ]);

        $rateTable = ContributionRateTable::factory()->for($company, 'company')->create([
            'agency' => 'sss',
            'effective_from' => '2026-01-01',
            'effective_to' => null,
            'is_active' => true,
        ]);
        ContributionRateBracket::factory()->for($rateTable, 'contributionRateTable')->create([
            'min_salary' => 0,
            'max_salary' => null,
            'employee_amount' => 180,
            'employer_amount' => 380,
        ]);

        $taxTable = TaxTable::factory()->for($company, 'company')->create([
            'effective_from' => '2026-01-01',
            'effective_to' => null,
            'is_active' => true,
        ]);
        TaxTableBracket::factory()->for($taxTable, 'taxTable')->create([
            'min_income' => 0,
            'max_income' => null,
            'base_tax' => 0,
            'excess_rate_percent' => 10,
        ]);

        return compact('company', 'group', 'period', 'employee');
    }

    public function test_processing_a_period_computes_earnings_contributions_tax_and_net_pay(): void
    {
        $user = $this->payrollAdmin();
        ['period' => $period, 'employee' => $employee] = $this->setUpScenario();

        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.process', $period))
            ->assertRedirect(route('admin.payroll.payroll-periods.show', $period));

        $period->refresh();
        $this->assertSame(PayrollPeriodStatus::ForReview, $period->status);
        $this->assertNotNull($period->processed_at);
        $this->assertSame($user->id, $period->processed_by);

        $item = PayrollItem::sole();
        $this->assertSame($employee->id, $item->employee_id);
        $this->assertEquals(30000, $item->basic_salary);
        $this->assertEquals(32000, $item->gross_earnings); // 30000 basic + 2000 monthly allowance, monthly period => factor 1
        $this->assertEquals(180, $item->total_employee_contributions);
        $this->assertEquals(380, $item->total_employer_contributions);
        // taxable = 32000 - 180 = 31820; tax = 0 + 31820 * 10% = 3182
        $this->assertEquals(3182, $item->tax_amount);
        // net = 32000 - 180 - 3182 = 28638
        $this->assertEquals(28638, $item->net_pay);

        $this->assertSame(2, $item->lines()->count());
        $this->assertSame(1, $item->contributions()->count());
    }

    public function test_reprocessing_replaces_the_prior_item_instead_of_duplicating(): void
    {
        $user = $this->payrollAdmin();
        ['period' => $period] = $this->setUpScenario();

        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.process', $period));
        $this->assertSame(1, PayrollItem::count());

        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.process', $period))
            ->assertRedirect();

        $this->assertSame(1, PayrollItem::count());
    }

    public function test_employee_in_a_different_payroll_group_is_excluded(): void
    {
        $user = $this->payrollAdmin();
        ['company' => $company, 'period' => $period] = $this->setUpScenario();

        $otherGroup = PayrollGroup::factory()->for($company, 'company')->create();
        $otherEmployee = Employee::factory()->for($company, 'company')->create();
        Employment::factory()->for($otherEmployee, 'employee')->for($company, 'company')->create([
            'payroll_group_id' => $otherGroup->id,
            'basic_salary' => 50000,
            'status' => EmploymentStatus::Active,
            'effective_date' => '2025-01-01',
            'end_date' => null,
        ]);

        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.process', $period));

        $this->assertSame(1, PayrollItem::count());
        $this->assertFalse(PayrollItem::where('employee_id', $otherEmployee->id)->exists());
    }

    public function test_period_past_review_cannot_be_processed(): void
    {
        $user = $this->payrollAdmin();
        ['period' => $period] = $this->setUpScenario();
        $period->update(['status' => PayrollPeriodStatus::Approved]);

        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.process', $period))
            ->assertSessionHasErrors('payrollPeriod');

        $this->assertSame(0, PayrollItem::count());
    }

    public function test_process_requires_payroll_process_permission_not_just_payroll_create(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['payroll.view', 'payroll.create']);
        ['period' => $period] = $this->setUpScenario();

        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.process', $period))
            ->assertForbidden();
    }

    public function test_period_and_item_show_pages_render(): void
    {
        $user = $this->payrollAdmin();
        ['period' => $period] = $this->setUpScenario();

        $this->actingAs($user)->post(route('admin.payroll.payroll-periods.process', $period));
        $item = PayrollItem::sole();

        $this->actingAs($user)->get(route('admin.payroll.payroll-periods.show', $period))
            ->assertOk()
            ->assertSee('Net pay');

        $this->actingAs($user)->get(route('admin.payroll.payroll-items.show', $item))
            ->assertOk()
            ->assertSee('Net pay');
    }
}
