<?php

namespace App\Models;

use App\Enums\GovernmentIdType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeGovernmentId extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['employee_id', 'id_type', 'id_number', 'issued_at', 'expires_at'];

    protected function casts(): array
    {
        return [
            'id_type' => GovernmentIdType::class,
            'issued_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
