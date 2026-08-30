<?php

namespace App\Models;

use App\Enums\CivilStatus;
use App\Enums\Gender;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'employee_number', 'first_name', 'middle_name', 'last_name', 'suffix',
        'preferred_name', 'birth_date', 'gender', 'civil_status', 'nationality', 'email',
        'mobile', 'profile_photo_path', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'gender' => Gender::class,
            'civil_status' => CivilStatus::class,
            'archived_at' => 'datetime',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim(collect([$this->first_name, $this->middle_name, $this->last_name, $this->suffix])
            ->filter()
            ->implode(' '));
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(EmployeeAddress::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(EmployeeContact::class);
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmployeeEmergencyContact::class);
    }

    public function governmentIds(): HasMany
    {
        return $this->hasMany(EmployeeGovernmentId::class);
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(EmployeeDependent::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(EmployeeNote::class);
    }

    public function employments(): HasMany
    {
        return $this->hasMany(Employment::class)->orderByDesc('effective_date');
    }

    public function currentEmployment(): HasOne
    {
        return $this->hasOne(Employment::class)->whereNull('end_date');
    }
}
