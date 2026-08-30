<?php

namespace App\Models;

use App\Enums\AddressType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'type', 'line1', 'line2', 'city', 'province_state', 'postal_code', 'country', 'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'type' => AddressType::class,
            'is_primary' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
