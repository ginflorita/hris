<?php

namespace App\Models;

use App\Enums\WorkflowApproverType;
use App\Enums\WorkflowInstanceStepStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowInstanceStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_instance_id', 'workflow_step_id', 'step_order', 'name',
        'approver_type', 'required_permission', 'status', 'acted_by', 'acted_at', 'comments',
    ];

    protected function casts(): array
    {
        return [
            'approver_type' => WorkflowApproverType::class,
            'status' => WorkflowInstanceStepStatus::class,
            'acted_at' => 'datetime',
        ];
    }

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class);
    }

    public function actedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
