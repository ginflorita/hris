<?php

namespace App\Models;

use App\Enums\GovernmentAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItemContribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_item_id', 'contribution_rate_table_id', 'contribution_rate_bracket_id',
        'agency', 'employee_amount', 'employer_amount',
    ];

    protected function casts(): array
    {
        return [
            'agency' => GovernmentAgency::class,
            'employee_amount' => 'decimal:2',
            'employer_amount' => 'decimal:2',
        ];
    }

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function contributionRateTable(): BelongsTo
    {
        return $this->belongsTo(ContributionRateTable::class);
    }

    public function contributionRateBracket(): BelongsTo
    {
        return $this->belongsTo(ContributionRateBracket::class);
    }
}
