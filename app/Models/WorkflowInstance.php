<?php

namespace App\Models;

use App\Enums\WorkflowInstanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkflowInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_definition_id', 'company_id', 'subject_type', 'subject_id',
        'initiated_by', 'status', 'current_step_order', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => WorkflowInstanceStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    public function workflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function instanceSteps(): HasMany
    {
        return $this->hasMany(WorkflowInstanceStep::class)->orderBy('step_order');
    }

    public function currentInstanceStep(): ?WorkflowInstanceStep
    {
        if ($this->current_step_order === null) {
            return null;
        }

        return $this->instanceSteps->firstWhere('step_order', $this->current_step_order);
    }
}
