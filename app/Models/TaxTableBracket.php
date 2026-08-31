<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxTableBracket extends Model
{
    use HasFactory;

    protected $fillable = [
        'tax_table_id', 'order', 'min_income', 'max_income', 'base_tax', 'excess_rate_percent',
    ];

    protected function casts(): array
    {
        return [
            'min_income' => 'decimal:2',
            'max_income' => 'decimal:2',
            'base_tax' => 'decimal:2',
            'excess_rate_percent' => 'decimal:2',
        ];
    }

    public function taxTable(): BelongsTo
    {
        return $this->belongsTo(TaxTable::class);
    }
}
