<?php

namespace App\Models;

use App\Enums\PayslipAccessAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipAccessLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['payroll_item_id', 'user_id', 'action', 'ip_address', 'user_agent'];

    protected function casts(): array
    {
        return [
            'action' => PayslipAccessAction::class,
            'created_at' => 'datetime',
        ];
    }

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
