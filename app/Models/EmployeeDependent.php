<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeDependent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['employee_id', 'name', 'relationship', 'birth_date', 'is_beneficiary'];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_beneficiary' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
