<?php

namespace App\Models;

use App\Enums\JobRequisitionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobRequisition extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'department_id', 'position_id', 'openings_count',
        'justification', 'target_start_date', 'status',
        'requested_by', 'approved_by', 'approved_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => JobRequisitionStatus::class,
            'target_start_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function jobPostings(): HasMany
    {
        return $this->hasMany(JobPosting::class);
    }
}
