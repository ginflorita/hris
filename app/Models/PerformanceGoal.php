<?php

namespace App\Models;

use App\Enums\PerformanceGoalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'performance_cycle_id', 'title', 'description', 'target_date',
        'weight', 'target_value', 'actual_value', 'unit', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'target_value' => 'decimal:2',
            'actual_value' => 'decimal:2',
            'status' => PerformanceGoalStatus::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function performanceCycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycle::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
