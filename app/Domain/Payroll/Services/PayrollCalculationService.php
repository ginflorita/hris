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
 * Reprocessing a Draft/ForReview period replaces each employee's
 * auto-generated lines and contributions wholesale, but preserves any
 * manual adjustment lines (PayrollItemLine::is_adjustment) already on
 * the item -- see processEmployee(). Manual adjustments only ever affect
 * gross_earnings/total_deductions/net_pay, both here and in
 * recalculateTotals() (called after adding/removing a single
 * adjustment) -- they never feed back into contributions or tax_amount,
 * on a reprocess or otherwise. Contributions key off basic_salary and
 * tax keys off the auto-generated gross only, matching how real
 * government contribution/tax tables are keyed off basic/regular pay,
 * not ad hoc corrections layered on top afterward.
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

        // Reprocessing wipes the prior item's auto-generated lines/contributions, but
        // manual adjustments survive -- captured here before the old item is cascade-deleted.
        $existingItem = PayrollItem::where('payroll_period_id', $period->id)->where('employee_id', $employee->id)->first();
        $preservedAdjustments = $existingItem
            ? $existingItem->lines()->where('is_adjustment', true)->get()
                ->map(fn ($line) => $line->only(['type', 'category', 'label', 'amount', 'is_adjustment', 'remarks', 'created_by']))
                ->all()
            : [];
        $existingItem?->delete();

        $totalDeductions = 0.0;
        foreach ($preservedAdjustments as $adjustment) {
            if ($adjustment['type'] === PayrollItemLineType::Deduction) {
                $totalDeductions += (float) $adjustment['amount'];
            } else {
                $grossEarnings = round($grossEarnings + (float) $adjustment['amount'], 2);
            }
        }
        $totalDeductions = round($totalDeductions, 2);

        $netPay = round($grossEarnings - $totalDeductions - $totalEmployeeContributions - $taxAmount, 2);

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
            'total_deductions' => $totalDeductions,
            'net_pay' => $netPay,
            'computed_at' => now(),
        ]);

        $payrollItem->lines()->createMany($lines);
        $payrollItem->lines()->createMany($preservedAdjustments);
        $payrollItem->contributions()->createMany($contributions);
    }

    /**
     * Recompute gross_earnings/total_deductions/net_pay from an item's
     * current lines after a manual adjustment is added or removed.
     * Deliberately does not touch contributions or tax_amount -- see the
     * class docblock for why. Called by PayrollItemAdjustmentController.
     */
    public function recalculateTotals(PayrollItem $item): void
    {
        $item->loadMissing('lines');

        $grossEarnings = round((float) $item->lines->where('type', PayrollItemLineType::Earning)->sum('amount'), 2);
        $totalDeductions = round((float) $item->lines->where('type', PayrollItemLineType::Deduction)->sum('amount'), 2);
        $netPay = round($grossEarnings - $totalDeductions - (float) $item->total_employee_contributions - (float) $item->tax_amount, 2);

        $item->update([
            'gross_earnings' => $grossEarnings,
            'total_deductions' => $totalDeductions,
            'net_pay' => $netPay,
        ]);
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
