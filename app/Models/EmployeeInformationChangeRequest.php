<?php

namespace App\Models;

use App\Domain\Workflow\Contracts\AppliesOnWorkflowApproval;
use App\Domain\Workflow\Contracts\HasWorkflowSubjectEmployee;
use App\Enums\CivilStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class EmployeeInformationChangeRequest extends Model implements AppliesOnWorkflowApproval, HasWorkflowSubjectEmployee
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'company_id', 'requested_mobile', 'requested_email',
        'requested_civil_status', 'requested_nationality', 'reason',
    ];

    protected function casts(): array
    {
        return [
            'requested_civil_status' => CivilStatus::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function workflowInstance(): MorphOne
    {
        return $this->morphOne(WorkflowInstance::class, 'subject');
    }

    public function workflowEmployee(): Employee
    {
        return $this->employee;
    }

    public function applyWorkflowApproval(): void
    {
        $this->employee->update(array_filter([
            'mobile' => $this->requested_mobile,
            'email' => $this->requested_email,
            'civil_status' => $this->requested_civil_status,
            'nationality' => $this->requested_nationality,
        ], fn ($value) => $value !== null));
    }
}
