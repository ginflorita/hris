<?php

namespace App\Models;

use App\Enums\ProficiencyLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSkill extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'skill_id', 'proficiency_level', 'assessed_at', 'assessed_by', 'notes'];

    protected function casts(): array
    {
        return [
            'proficiency_level' => ProficiencyLevel::class,
            'assessed_at' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assessed_by');
    }
}
