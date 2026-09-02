<?php

namespace App\Models;

use App\Enums\PerformanceImprovementPlanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceImprovementPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'performance_review_id', 'initiated_by', 'reason', 'goals',
        'start_date', 'end_date', 'status', 'outcome_notes', 'closed_at', 'closed_by', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => PerformanceImprovementPlanStatus::class,
            'closed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function performanceReview(): BelongsTo
    {
        return $this->belongsTo(PerformanceReview::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'initiated_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
