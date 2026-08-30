<?php

namespace App\Models;

use App\Enums\LeaveTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'leave_type_id', 'leave_request_id', 'type', 'amount',
        'balance_after', 'reason', 'date', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => LeaveTransactionType::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'date' => 'date:Y-m-d',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
