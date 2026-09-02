<?php

namespace App\Models;

use App\Enums\CareerDevelopmentPlanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerDevelopmentPlan extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'target_position_id', 'target_date', 'development_actions', 'status', 'created_by'];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'status' => CareerDevelopmentPlanStatus::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function targetPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'target_position_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
