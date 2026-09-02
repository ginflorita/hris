<?php

namespace App\Models;

use App\Enums\SuccessionReadiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuccessionCandidate extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'position_id', 'readiness', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['readiness' => SuccessionReadiness::class];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
