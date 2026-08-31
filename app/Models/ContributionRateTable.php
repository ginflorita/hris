<?php

namespace App\Models;

use App\Enums\GovernmentAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContributionRateTable extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['company_id', 'agency', 'name', 'effective_from', 'effective_to', 'is_active'];

    protected function casts(): array
    {
        return [
            'agency' => GovernmentAgency::class,
            'effective_from' => 'date:Y-m-d',
            'effective_to' => 'date:Y-m-d',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function brackets(): HasMany
    {
        return $this->hasMany(ContributionRateBracket::class)->orderBy('order');
    }
}
