<?php

namespace App\Models;

use App\Enums\EmploymentChangeType;
use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Enums\WorkArrangement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'company_id', 'department_id', 'position_id', 'salary_grade_id', 'payroll_group_id', 'branch_id', 'location_id', 'manager_id',
        'employment_type', 'work_arrangement', 'status', 'change_type',
        'probation_ends_at', 'regularized_at', 'basic_salary', 'contract_start_date', 'contract_end_date',
        'separation_reason', 'remarks', 'effective_date', 'end_date', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'work_arrangement' => WorkArrangement::class,
            'status' => EmploymentStatus::class,
            'change_type' => EmploymentChangeType::class,
            'probation_ends_at' => 'date',
            'regularized_at' => 'date',
            'basic_salary' => 'decimal:2',
            'contract_start_date' => 'date',
            'contract_end_date' => 'date',
            'effective_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function isCurrent(): bool
    {
        return $this->end_date === null;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function salaryGrade(): BelongsTo
    {
        return $this->belongsTo(SalaryGrade::class);
    }

    public function payrollGroup(): BelongsTo
    {
        return $this->belongsTo(PayrollGroup::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
