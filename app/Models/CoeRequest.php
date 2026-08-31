<?php

namespace App\Models;

use App\Enums\CoeRequestStatus;
use App\Enums\CoeRequestType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'company_id', 'type', 'purpose', 'status',
        'requested_by', 'approved_by', 'approved_at', 'rejection_reason',
        'snapshot_position', 'snapshot_department', 'snapshot_employment_status',
        'snapshot_date_hired', 'snapshot_monthly_salary',
    ];

    protected function casts(): array
    {
        return [
            'type' => CoeRequestType::class,
            'status' => CoeRequestStatus::class,
            'approved_at' => 'datetime',
            'snapshot_date_hired' => 'date',
            'snapshot_monthly_salary' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
