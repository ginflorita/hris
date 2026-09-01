<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\JobOfferStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id', 'job_posting_id', 'status', 'applied_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'applied_at' => 'datetime',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class)->orderBy('scheduled_at');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class)->orderByDesc('created_at');
    }

    public function jobOffers(): HasMany
    {
        return $this->hasMany(JobOffer::class)->orderByDesc('created_at');
    }

    /**
     * At most one open (Pending) offer per application at a time -- see
     * the job_offers migration for why this is an app-level rule rather
     * than a DB constraint. Queried fresh rather than filtering a loaded
     * jobOffers collection so callers don't need to remember to eager
     * load it first.
     */
    public function hasPendingJobOffer(): bool
    {
        return $this->jobOffers()->where('status', JobOfferStatus::Pending)->exists();
    }
}
