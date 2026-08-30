<?php

namespace App\Models;

use App\Enums\HolidayType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Holiday extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['company_id', 'name', 'date', 'type', 'is_active'];

    protected function casts(): array
    {
        return [
            // Explicit Y-m-d format (not the bare 'date' cast) so the
            // stored value matches the plain date string the "date"
            // <input> submits — otherwise Eloquent serializes to a full
            // 'Y-m-d H:i:s' datetime on write, and Rule::unique's raw
            // string comparison against request input silently never
            // matches an existing row (caught by the DB's own unique
            // constraint instead, as a raw SQL exception).
            'date' => 'date:Y-m-d',
            'type' => HolidayType::class,
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
