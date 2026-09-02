<?php

namespace App\Models;

use App\Enums\TrainingEnrollmentStatus;
use App\Enums\TrainingSessionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSession extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'training_course_id', 'start_date', 'end_date', 'location', 'capacity', 'cost', 'status'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'cost' => 'decimal:2',
            'status' => TrainingSessionStatus::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(TrainingCourse::class, 'training_course_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class);
    }

    /**
     * Enrolled + Completed count against capacity -- Cancelled/NoShow
     * free up the slot they held.
     */
    public function occupiedSeats(): int
    {
        return $this->enrollments()
            ->whereIn('status', [TrainingEnrollmentStatus::Enrolled, TrainingEnrollmentStatus::Completed])
            ->count();
    }
}
