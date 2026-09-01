<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeOnboarding extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'onboarding_template_id', 'assigned_by', 'notes'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(OnboardingTemplate::class, 'onboarding_template_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(EmployeeOnboardingTask::class)->orderBy('sequence');
    }

    /**
     * No tasks assigned counts as complete (nothing left to do) rather
     * than stuck -- only an actual incomplete task blocks this.
     */
    public function isComplete(): bool
    {
        return $this->tasks->isEmpty() || $this->tasks->every(fn (EmployeeOnboardingTask $task) => $task->is_completed);
    }

    public function progressPercentage(): int
    {
        $total = $this->tasks->count();

        if ($total === 0) {
            return 100;
        }

        return (int) round($this->tasks->where('is_completed', true)->count() / $total * 100);
    }
}
