<?php

namespace App\Models;

use App\Enums\TrainingEnrollmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'training_session_id', 'status', 'enrolled_at',
        'certificate_number', 'certificate_issued_at', 'certificate_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TrainingEnrollmentStatus::class,
            'enrolled_at' => 'datetime',
            'certificate_issued_at' => 'date',
            'certificate_expires_at' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
    }
}
