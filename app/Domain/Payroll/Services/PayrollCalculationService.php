<?php

namespace App\Domain\Payroll\Services;

use App\Enums\CompensationFrequency;
use App\Enums\EmploymentStatus;
use App\Enums\PayrollItemLineType;
use App\Enums\PayrollPeriodStatus;
use App\Models\CompensationItem;
use App\Models\ContributionRateTable;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\TaxTable;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Controller stays thin; all payroll math lives here (CLAUDE.md "Payroll
 * logic never lives in controllers"). Every number either comes straight
 * from stored data (basic_salary, active CompensationItem rows) or from
 * the versioned rate tables Phase 11a built -- nothing here hard-codes a
 * rate. See CLAUDE.md "Payroll" for what's deliberately out of scope
 * (overtime pay, holiday pay) and why, and for the proration convention
 * (App\Enums\PayFrequency::periodsPerYear()) this relies on.
 *
 * Reprocessing a Draft/ForReview period replaces each employee's item
 * wholesale (delete + regenerate, cascading its lines/contributions) --
 * safe because nothing downstream depends on the old numbers yet.
 * Phase 11d's manual adjustment lines will need this to preserve
 * is_adjustment lines instead of blanket-deleting; not needed today
 * since nothing produces one yet.
 */
class PayrollCalculationService
{
    public function process(PayrollPeriod $period, ?User $actor = null): int
    {
        return DB::transaction(function () use ($period, $actor) {
            $factor = 12 / $period->payrollGroup->pay_frequency->periodsPerYear();

            $employees = $this->eligibleEmployees($period);
            $contributionTables = $this->activeContributionTables($period);
            $taxTable = $this->activeTaxTable($period);

            foreach ($employees as $employee) {
                $this->processEmployee($period, $employee, $factor, $contributionTables, $taxTable);
            }

            $period->update([
                'status' => PayrollPeriodStatus::ForReview,
                'processed_at' => now(),
                'processed_by' => $actor?->id,
            ]);

            return $employees->count();
        });
    }

    /**
     * @return Collection<int, Employee>
     */
    private function eligibleEmployees(PayrollPeriod $period): Collection
    {
        return Employee::query()
            ->where('company_id', $period->company_id)
            ->whereNull('archived_at')
            ->whereHas('currentEmployment', function ($query) use ($period) {
                $query->where('payroll_group_id', $period->payroll_group_id)
                    ->where('status', EmploymentStatus::Active->value);
            })
            ->with([
                'currentEmployment',
                'compensationItems' => fn ($query) => $query->where('is_active', true),
            ])
            ->get();
    }

    /**
     * One active table per agency; if more than one somehow overlaps for
     * the same agency, the most recently effective one wins.
     *
     * @return Collection<string, ContributionRateTable>
     */
    private function activeContributionTables(PayrollPeriod $period): Collection
    {
        return ContributionRateTable::query()
            ->where('company_id', $period->company_id)
            ->where('is_active', true)
            ->where('effective_from', '<=', $period->end_date)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $period->start_date))
            ->with('brackets')
            ->orderByDesc('effective_from')
            ->get()
            ->unique(fn (ContributionRateTable $table) => $table->agency->value);
    }

    private function activeTaxTable(PayrollPeriod $period): ?TaxTable
    {
        return TaxTable::query()
            ->where('company_id', $period->company_id)
            ->where('is_active', true)
            ->where('effective_from', '<=', $period->end_date)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $period->start_date))
            ->with('brackets')
            ->orderByDesc('effective_from')
            ->first();
    }

    /**
     * @param  Collection<string, ContributionRateTable>  $contributionTables
     */
    private function processEmployee(
        PayrollPeriod $period,
        Employee $employee,
        float $factor,
        Collection $contributionTables,
        ?TaxTable $taxTable,
    ): void {
        $employment = $employee->currentEmployment;
        $basicSalary = round((float) $employment->basic_salary * $factor, 2);

        $lines = [[
            'type' => PayrollItemLineType::Earning,
            'category' => 'basic_salary',
            'label' => 'Basic Pay',
            'amount' => $basicSalary,
        ]];

        foreach ($employee->compensationItems as $item) {
            $amount = $this->compensationItemAmountForPeriod($item, $period, $factor);

            if ($amount !== null) {
                $lines[] = [
                    'type' => PayrollItemLineType::Earning,
                    'category' => $item->type->value,
                    'label' => $item->name,
                    'amount' => $amount,
                ];
            }
        }

        $grossEarnings = round(array_sum(array_column($lines, 'amount')), 2);

        $contributions = [];
        foreach ($contributionTables as $table) {
            $bracket = $table->brackets->first(fn ($b) => $basicSalary >= $b->min_salary && ($b->max_salary === null || $basicSalary <= $b->max_salary));

            if ($bracket) {
                $contributions[] = [
                    'contribution_rate_table_id' => $table->id,
                    'contribution_rate_bracket_id' => $bracket->id,
                    'agency' => $table->agency,
                    'employee_amount' => $bracket->employee_amount,
                    'employer_amount' => $bracket->employer_amount,
                ];
            }
        }

        $totalEmployeeContributions = round(array_sum(array_column($contributions, 'employee_amount')), 2);
        $totalEmployerContributions = round(array_sum(array_column($contributions, 'employer_amount')), 2);

        $taxableIncome = max(0, $grossEarnings - $totalEmployeeContributions);
        $taxAmount = 0.0;
        $appliedTaxTable = null;

        if ($taxTable) {
            $bracket = $taxTable->brackets->first(fn ($b) => $taxableIncome >= $b->min_income && ($b->max_income === null || $taxableIncome <= $b->max_income));

            if ($bracket) {
                $taxAmount = round((float) $bracket->base_tax + max(0, $taxableIncome - (float) $bracket->min_income) * ((float) $bracket->excess_rate_percent / 100), 2);
                $appliedTaxTable = $taxTable;
            }
        }

        $netPay = round($grossEarnings - $totalEmployeeContributions - $taxAmount, 2);

        // Reprocessing wipes the prior item for this employee/period wholesale -- see class docblock.
        PayrollItem::where('payroll_period_id', $period->id)->where('employee_id', $employee->id)->delete();

        $payrollItem = PayrollItem::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $employee->id,
            'company_id' => $period->company_id,
            'basic_salary' => $basicSalary,
            'gross_earnings' => $grossEarnings,
            'total_employee_contributions' => $totalEmployeeContributions,
            'total_employer_contributions' => $totalEmployerContributions,
            'tax_table_id' => $appliedTaxTable?->id,
            'tax_amount' => $taxAmount,
            'total_deductions' => 0,
            'net_pay' => $netPay,
            'computed_at' => now(),
        ]);

        $payrollItem->lines()->createMany($lines);
        $payrollItem->contributions()->createMany($contributions);
    }

    /**
     * Monthly items prorate the same way basic salary does and repeat
     * every period they're effective for. OneTime and Annual items both
     * pay out in full, exactly once, in whichever period contains their
     * effective_date -- CompensationItem has no recurrence field, so an
     * "Annual" item isn't repeated on some inferred anniversary; see
     * CLAUDE.md "Payroll" for why that stays a deliberate simplification
     * rather than an invented one.
     */
    private function compensationItemAmountForPeriod(CompensationItem $item, PayrollPeriod $period, float $factor): ?float
    {
        if ($item->effective_date->gt($period->end_date)) {
            return null;
        }
        if ($item->end_date !== null && $item->end_date->lt($period->start_date)) {
            return null;
        }

        return match ($item->frequency) {
            CompensationFrequency::Monthly => round((float) $item->amount * $factor, 2),
            CompensationFrequency::OneTime, CompensationFrequency::Annual => $item->effective_date->between($period->start_date, $period->end_date)
                ? round((float) $item->amount, 2)
                : null,
        };
    }
}
