@php($role = $role ?? null)
@php($assignedPermissions = $role ? $role->permissions->pluck('name')->all() : old('permissions', []))

<div class="mb-3">
    <label class="form-label" for="name">Name</label>
    <input id="name" type="text" name="name" value="{{ old('name', $role?->name) }}"
           class="form-control @error('name') is-invalid @enderror"
           {{ $role?->name === \App\Enums\DefaultRole::Superadmin->value ? 'readonly' : '' }}>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="data_scope">Data scope</label>
    <select id="data_scope" name="data_scope" class="form-select @error('data_scope') is-invalid @enderror">
        @foreach ($scopes as $scope)
            <option value="{{ $scope->value }}" {{ old('data_scope', $role?->data_scope?->value) === $scope->value ? 'selected' : '' }}>
                {{ ucfirst($scope->value) }}
            </option>
        @endforeach
    </select>
    @error('data_scope')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <div class="form-text">
        How far this role's permissions reach. Only Own/All are wired up so far — see CLAUDE.md "Data scope".
    </div>
</div>

<div class="mb-3">
    <label class="form-label d-block">Permissions</label>
    <div class="row">
        @foreach ($permissionsByModule as $module => $permissions)
            <div class="col-12 col-md-6 col-lg-4 mb-3">
                <div class="fw-semibold small text-uppercase text-body-secondary mb-1">{{ $module }}</div>
                @foreach ($permissions as $permission)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                               id="perm-{{ $permission->id }}"
                               {{ in_array($permission->name, $assignedPermissions) ? 'checked' : '' }}>
                        <label class="form-check-label" for="perm-{{ $permission->id }}">{{ $permission->name }}</label>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
