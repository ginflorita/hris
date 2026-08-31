<?php

namespace App\Models;

use App\Enums\JobPostingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPosting extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_requisition_id', 'company_id', 'title', 'description',
        'is_internal', 'status', 'published_at', 'closes_at',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
            'status' => JobPostingStatus::class,
            'published_at' => 'datetime',
            'closes_at' => 'date',
        ];
    }

    public function jobRequisition(): BelongsTo
    {
        return $this->belongsTo(JobRequisition::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
