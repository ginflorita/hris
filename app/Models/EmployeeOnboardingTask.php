<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeOnboardingTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_onboarding_id', 'title', 'description', 'sequence',
        'is_completed', 'completed_at', 'completed_by',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function employeeOnboarding(): BelongsTo
    {
        return $this->belongsTo(EmployeeOnboarding::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
