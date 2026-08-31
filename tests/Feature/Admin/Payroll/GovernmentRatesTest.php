<?php

namespace Tests\Feature\Admin\Payroll;

use App\Models\Company;
use App\Models\ContributionRateBracket;
use App\Models\ContributionRateTable;
use App\Models\TaxTable;
use App\Models\TaxTableBracket;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GovernmentRatesTest extends TestCase
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
        $user->givePermissionTo(['payroll.view', 'payroll.create']);

        return $user;
    }

    public function test_contribution_rate_table_and_bracket_crud(): void
    {
        $user = $this->payrollAdmin();
        $company = Company::factory()->create();

        $this->actingAs($user)->post(route('admin.payroll.contribution-rate-tables.store'), [
            'company_id' => $company->id,
            'agency' => 'sss',
            'name' => '2026 SSS Table',
            'effective_from' => '2026-01-01',
        ])->assertRedirect();

        $table = ContributionRateTable::sole();
        $this->assertTrue($table->is_active);
        $this->assertSame('sss', $table->agency->value);

        $this->actingAs($user)->post(route('admin.payroll.contribution-rate-tables.brackets.store', $table), [
            'order' => 1,
            'min_salary' => 0,
            'max_salary' => 10000,
            'employee_amount' => 180,
            'employer_amount' => 380,
        ])->assertRedirect();

        $bracket = ContributionRateBracket::sole();
        $this->assertSame($table->id, $bracket->contribution_rate_table_id);

        $this->actingAs($user)->put(route('admin.payroll.contribution-rate-tables.brackets.update', [$table, $bracket]), [
            'order' => 1,
            'min_salary' => 0,
            'max_salary' => 12000,
            'employee_amount' => 200,
            'employer_amount' => 400,
        ])->assertRedirect();
        $this->assertEquals(12000, $bracket->fresh()->max_salary);

        // Deleting the table cascades its brackets.
        $this->actingAs($user)->delete(route('admin.payroll.contribution-rate-tables.destroy', $table))->assertRedirect();
        $this->assertModelMissing($bracket);
    }

    public function test_contribution_rate_bracket_from_another_table_404s(): void
    {
        $user = $this->payrollAdmin();
        $tableA = ContributionRateTable::factory()->create();
        $tableB = ContributionRateTable::factory()->create();
        $bracket = ContributionRateBracket::factory()->for($tableB, 'contributionRateTable')->create();

        $this->actingAs($user)->delete(route('admin.payroll.contribution-rate-tables.brackets.destroy', [$tableA, $bracket]))
            ->assertNotFound();
    }

    public function test_tax_table_and_bracket_crud(): void
    {
        $user = $this->payrollAdmin();
        $company = Company::factory()->create();

        $this->actingAs($user)->post(route('admin.payroll.tax-tables.store'), [
            'company_id' => $company->id,
            'name' => '2026 Withholding Tax Table',
            'effective_from' => '2026-01-01',
        ])->assertRedirect();

        $table = TaxTable::sole();

        $this->actingAs($user)->post(route('admin.payroll.tax-tables.brackets.store', $table), [
            'order' => 1,
            'min_income' => 0,
            'max_income' => 20833,
            'base_tax' => 0,
            'excess_rate_percent' => 0,
        ])->assertRedirect();

        $bracket = TaxTableBracket::sole();
        $this->assertSame($table->id, $bracket->tax_table_id);

        $this->actingAs($user)->delete(route('admin.payroll.tax-tables.brackets.destroy', [$table, $bracket]))->assertRedirect();
        $this->assertModelMissing($bracket);
    }

    public function test_bracket_max_must_exceed_min(): void
    {
        $user = $this->payrollAdmin();
        $table = ContributionRateTable::factory()->create();

        $this->actingAs($user)->post(route('admin.payroll.contribution-rate-tables.brackets.store', $table), [
            'min_salary' => 10000,
            'max_salary' => 5000,
            'employee_amount' => 100,
            'employer_amount' => 200,
        ])->assertSessionHasErrors('max_salary');
    }

    public function test_without_permission_gets_403(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)->get(route('admin.payroll.contribution-rate-tables.index'))->assertForbidden();
        $this->actingAs($plain)->get(route('admin.payroll.tax-tables.index'))->assertForbidden();
    }
}
