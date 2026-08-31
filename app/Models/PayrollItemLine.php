<?php

namespace App\Models;

use App\Enums\PayrollItemLineType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItemLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_item_id', 'type', 'category', 'label', 'amount', 'is_adjustment', 'remarks', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => PayrollItemLineType::class,
            'amount' => 'decimal:2',
            'is_adjustment' => 'boolean',
        ];
    }

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
