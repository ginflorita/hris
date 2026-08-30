<?php

namespace App\Models;

use App\Enums\AccrualFrequency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeavePolicy extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'leave_type_id', 'name', 'accrual_rate', 'accrual_frequency',
        'max_balance', 'carry_over_days', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'accrual_rate' => 'decimal:2',
            'accrual_frequency' => AccrualFrequency::class,
            'max_balance' => 'decimal:2',
            'carry_over_days' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
