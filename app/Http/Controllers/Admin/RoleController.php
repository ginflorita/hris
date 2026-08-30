<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DataScope;
use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

        return view('admin.roles.index', [
            'roles' => Role::withCount(['permissions', 'users'])->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);

        return view('admin.roles.create', [
            'permissionsByModule' => $this->permissionsByModule(),
            'scopes' => DataScope::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $validated = $this->validateRole($request);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'data_scope' => $validated['data_scope'],
        ]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('status', 'Role created.');
    }

    public function edit(Role $role): View
    {
        // 'update' rather than 'view': nothing on this page is meant to be
        // reachable-but-read-only for the Superadmin role, so show the
        // same 403 the list's hidden Edit link implies rather than a form
        // that then rejects the save.
        $this->authorize('update', $role);

        return view('admin.roles.edit', [
            'role' => $role->load('permissions'),
            'permissionsByModule' => $this->permissionsByModule(),
            'scopes' => DataScope::cases(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $validated = $this->validateRole($request, $role);

        $role->update(['name' => $validated['name'], 'data_scope' => $validated['data_scope']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('status', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'Reassign or remove the members of this role before deleting it.']);
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('status', 'Role deleted.');
    }

    /**
     * @return Collection<string, Collection<int, Permission>>
     */
    private function permissionsByModule(): Collection
    {
        return Permission::orderBy('name')->get()->groupBy(fn (Permission $permission) => Str::beforeLast($permission->name, '.'));
    }

    /**
     * @return array{name: string, data_scope: DataScope, permissions: array<int, string>}
     */
    private function validateRole(Request $request, ?Role $role = null): array
    {
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role?->id),
            ],
            'data_scope' => ['required', Rule::enum(DataScope::class)],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $validated['data_scope'] = DataScope::from($validated['data_scope']);

        return $validated;
    }
}
