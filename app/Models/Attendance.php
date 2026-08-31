<?php

namespace App\Models;

use App\Enums\AttendanceSource;
use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'company_id', 'date', 'time_in', 'time_out', 'break_start', 'break_end',
        'source', 'status', 'late_minutes', 'undertime_minutes', 'overtime_minutes', 'remarks',
        'is_corrected', 'corrected_by', 'corrected_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'time_in' => 'datetime',
            'time_out' => 'datetime',
            'break_start' => 'datetime',
            'break_end' => 'datetime',
            'source' => AttendanceSource::class,
            'status' => AttendanceStatus::class,
            'is_corrected' => 'boolean',
            'corrected_at' => 'datetime',
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

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }

    public function correctionLogs(): HasMany
    {
        return $this->hasMany(AttendanceCorrectionLog::class);
    }

    public function correctionRequests(): HasMany
    {
        return $this->hasMany(AttendanceCorrectionRequest::class);
    }
}
