<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContributionRateBracket extends Model
{
    use HasFactory;

    protected $fillable = [
        'contribution_rate_table_id', 'order', 'min_salary', 'max_salary', 'employee_amount', 'employer_amount',
    ];

    protected function casts(): array
    {
        return [
            'min_salary' => 'decimal:2',
            'max_salary' => 'decimal:2',
            'employee_amount' => 'decimal:2',
            'employer_amount' => 'decimal:2',
        ];
    }

    public function contributionRateTable(): BelongsTo
    {
        return $this->belongsTo(ContributionRateTable::class);
    }
}
