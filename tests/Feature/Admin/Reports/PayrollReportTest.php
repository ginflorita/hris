<?php

namespace Tests\Feature\Admin\Reports;

use App\Enums\GovernmentAgency;
use App\Enums\PayrollItemLineType;
use App\Models\Company;
use App\Models\PayrollItem;
use App\Models\PayrollItemContribution;
use App\Models\PayrollItemLine;
use App\Models\PayrollPeriod;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollReportTest extends TestCase
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
        $user->givePermissionTo('payroll.view');

        return $user;
    }

    public function test_payroll_report_requires_payroll_view_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('reports.view');

        $this->actingAs($user)->get(route('admin.reports.payroll.index'))->assertForbidden();
        $this->actingAs($this->payrollAdmin())->get(route('admin.reports.payroll.index'))->assertOk();
    }

    public function test_payroll_report_totals_and_breakdowns_for_the_selected_period(): void
    {
        $company = Company::factory()->create();
        $period = PayrollPeriod::factory()->for($company, 'company')->create();

        $itemA = PayrollItem::factory()->for($period, 'payrollPeriod')->for($company, 'company')->create([
            'gross_earnings' => 30000,
            'total_deductions' => 1000,
            'tax_amount' => 500,
            'net_pay' => 28500,
        ]);
        PayrollItemLine::factory()->for($itemA, 'payrollItem')->create([
            'type' => PayrollItemLineType::Deduction,
            'category' => 'loan',
            'amount' => 1000,
        ]);
        PayrollItemContribution::factory()->for($itemA, 'payrollItem')->create([
            'agency' => GovernmentAgency::SSS,
            'employee_amount' => 180,
            'employer_amount' => 380,
        ]);

        $itemB = PayrollItem::factory()->for($period, 'payrollPeriod')->for($company, 'company')->create([
            'gross_earnings' => 20000,
            'total_deductions' => 500,
            'tax_amount' => 200,
            'net_pay' => 19300,
        ]);
        PayrollItemLine::factory()->for($itemB, 'payrollItem')->create([
            'type' => PayrollItemLineType::Deduction,
            'category' => 'loan',
            'amount' => 500,
        ]);

        $this->actingAs($this->payrollAdmin())
            ->get(route('admin.reports.payroll.index', ['payroll_period_id' => $period->id]))
            ->assertOk()
            ->assertViewHas('totals', fn ($totals) => $totals['employeeCount'] === 2
                && (float) $totals['grossEarnings'] === 50000.0
                && (float) $totals['totalDeductions'] === 1500.0
                && (float) $totals['netPay'] === 47800.0)
            ->assertViewHas('byDeductionCategory', fn ($rows) => (float) $rows['loan'] === 1500.0)
            ->assertViewHas('byAgency', fn ($rows) => (float) $rows['sss']['employee'] === 180.0
                && (float) $rows['sss']['employer'] === 380.0);
    }

    public function test_payroll_report_defaults_to_the_most_recent_period_when_none_is_selected(): void
    {
        $company = Company::factory()->create();
        $older = PayrollPeriod::factory()->for($company, 'company')->create(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);
        $newer = PayrollPeriod::factory()->for($company, 'company')->create(['start_date' => '2026-02-01', 'end_date' => '2026-02-28']);

        $this->actingAs($this->payrollAdmin())
            ->get(route('admin.reports.payroll.index'))
            ->assertOk()
            ->assertViewHas('selectedPeriod', fn ($period) => $period->id === $newer->id);
    }

    public function test_payroll_report_handles_a_company_with_no_payroll_periods(): void
    {
        $company = Company::factory()->create();

        $this->actingAs($this->payrollAdmin())
            ->get(route('admin.reports.payroll.index', ['company_id' => $company->id]))
            ->assertOk()
            ->assertViewHas('selectedPeriod', fn ($period) => $period === null);
    }
}
