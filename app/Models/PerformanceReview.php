<?php

namespace App\Models;

use App\Enums\PerformanceReviewStatus;
use App\Enums\PerformanceReviewType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'performance_cycle_id', 'reviewer_id', 'type',
        'rating', 'comments', 'status', 'submitted_at', 'acknowledged_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => PerformanceReviewType::class,
            'rating' => 'integer',
            'status' => PerformanceReviewStatus::class,
            'submitted_at' => 'datetime',
            'acknowledged_at' => 'datetime',
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reviewer_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
