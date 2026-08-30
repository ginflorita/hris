<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryGrade extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'salary_structure_id', 'name', 'code', 'min_salary', 'mid_salary', 'max_salary', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_salary' => 'decimal:2',
            'mid_salary' => 'decimal:2',
            'max_salary' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(SalaryStructure::class);
    }

    public function employments(): HasMany
    {
        return $this->hasMany(Employment::class);
    }
}
