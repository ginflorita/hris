<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

/**
 * Read-only: the permission catalog is developer-defined (see
 * RoleAndPermissionSeeder), not something admins add to at runtime — this
 * just gives visibility into what exists and which roles have it. Editing
 * *which permissions a role has* is done from the role editor, not here.
 */
class PermissionController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

        $permissions = Permission::with('roles')->orderBy('name')->get()
            ->groupBy(fn (Permission $permission) => Str::beforeLast($permission->name, '.'));

        return view('admin.permissions.index', ['permissionsByModule' => $permissions]);
    }
}
