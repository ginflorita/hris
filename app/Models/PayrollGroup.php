<?php

namespace App\Models;

use App\Enums\PayFrequency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['company_id', 'name', 'code', 'pay_frequency', 'is_active'];

    protected function casts(): array
    {
        return [
            'pay_frequency' => PayFrequency::class,
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function payrollPeriods(): HasMany
    {
        return $this->hasMany(PayrollPeriod::class);
    }

    public function employments(): HasMany
    {
        return $this->hasMany(Employment::class);
    }
}
