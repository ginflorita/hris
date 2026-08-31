<?php

namespace App\Models;

use App\Enums\AssessmentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id', 'type', 'due_at', 'completed_at', 'score', 'passed', 'notes', 'assessed_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => AssessmentType::class,
            'due_at' => 'date',
            'completed_at' => 'datetime',
            'score' => 'decimal:2',
            'passed' => 'boolean',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }
}
