<?php

namespace App\Models;

use App\Enums\DataScope;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'data_scope' => DataScope::class,
        ];
    }
}
