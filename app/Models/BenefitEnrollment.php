<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BenefitEnrollment extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'benefit_plan_id', 'employee_contribution', 'employer_contribution', 'effective_date', 'end_date', 'created_by'];

    protected function casts(): array
    {
        return [
            'employee_contribution' => 'decimal:2',
            'employer_contribution' => 'decimal:2',
            'effective_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(BenefitPlan::class, 'benefit_plan_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function coveredDependents(): BelongsToMany
    {
        return $this->belongsToMany(EmployeeDependent::class, 'benefit_enrollment_dependents');
    }

    public function isCurrent(): bool
    {
        return $this->end_date === null;
    }
}
