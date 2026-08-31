<?php

namespace App\Models;

use App\Enums\InterviewRecommendation;
use App\Enums\InterviewStatus;
use App\Enums\InterviewType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interview extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id', 'interviewer_id', 'type', 'scheduled_at', 'location',
        'status', 'rating', 'recommendation', 'feedback',
    ];

    protected function casts(): array
    {
        return [
            'type' => InterviewType::class,
            'scheduled_at' => 'datetime',
            'status' => InterviewStatus::class,
            'recommendation' => InterviewRecommendation::class,
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'interviewer_id');
    }
}
