<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_period_id', 'employee_id', 'company_id', 'basic_salary', 'gross_earnings',
        'total_employee_contributions', 'total_employer_contributions', 'tax_table_id', 'tax_amount',
        'total_deductions', 'net_pay', 'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'gross_earnings' => 'decimal:2',
            'total_employee_contributions' => 'decimal:2',
            'total_employer_contributions' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_pay' => 'decimal:2',
            'computed_at' => 'datetime',
        ];
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function taxTable(): BelongsTo
    {
        return $this->belongsTo(TaxTable::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PayrollItemLine::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(PayrollItemContribution::class);
    }

    /**
     * Basic, non-blocking sanity checks surfaced on the period/item show
     * pages -- Phase 11 has nowhere to attach a hard block (Approve/
     * Reject is Phase 12's job), so these are flags for a reviewer to
     * look at, not gates. See CLAUDE.md "Payroll" for why the list stays
     * this short rather than a broader validation rules engine.
     *
     * @return array<int, string>
     */
    public function validationIssues(): array
    {
        $issues = [];

        if ((float) $this->net_pay < 0) {
            $issues[] = 'Net pay is negative -- contributions and tax exceed gross earnings.';
        }

        if ((float) $this->gross_earnings > 0 && $this->tax_table_id === null) {
            $issues[] = 'No active tax table matched this period -- tax was not computed.';
        }

        return $issues;
    }
}
