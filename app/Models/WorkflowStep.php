<?php

namespace App\Models;

use App\Enums\WorkflowApproverType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_definition_id', 'step_order', 'name', 'approver_type', 'required_permission',
    ];

    protected function casts(): array
    {
        return [
            'approver_type' => WorkflowApproverType::class,
        ];
    }

    public function workflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }
}
