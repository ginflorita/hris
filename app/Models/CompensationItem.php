<?php

namespace App\Models;

use App\Enums\CompensationFrequency;
use App\Enums\CompensationItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompensationItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id', 'company_id', 'type', 'name', 'amount', 'frequency',
        'effective_date', 'end_date', 'remarks', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => CompensationItemType::class,
            'amount' => 'decimal:2',
            'frequency' => CompensationFrequency::class,
            'effective_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'is_active' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
