<?php

namespace App\Models;

use App\Enums\AttendanceCorrectionRequestStatus;
use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id', 'employee_id', 'company_id',
        'requested_time_in', 'requested_time_out', 'requested_status',
        'reason', 'status', 'requested_by', 'approved_by', 'approved_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'requested_status' => AttendanceStatus::class,
            'status' => AttendanceCorrectionRequestStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
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
