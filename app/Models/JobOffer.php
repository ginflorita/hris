<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\JobOfferStatus;
use App\Enums\WorkArrangement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id', 'department_id', 'position_id',
        'employment_type', 'work_arrangement', 'offered_salary', 'start_date', 'expires_at', 'notes',
        'status', 'extended_by', 'responded_at', 'decision_reason',
        'converted_employee_id', 'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'work_arrangement' => WorkArrangement::class,
            'offered_salary' => 'decimal:2',
            'start_date' => 'date',
            'expires_at' => 'date',
            'status' => JobOfferStatus::class,
            'responded_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function extendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'extended_by');
    }

    public function convertedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'converted_employee_id');
    }
}
