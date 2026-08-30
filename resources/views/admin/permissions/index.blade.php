@extends('layouts.admin')

@section('title', 'Permissions')

@php($breadcrumbs = [['label' => 'Administration'], ['label' => 'Permissions']])

@section('content')
    <p class="text-body-secondary small">
        The permission catalog is fixed in code (<code>RoleAndPermissionSeeder</code>) — this is a read-only view of
        what exists and which roles currently have each one. To change what a role can do, edit the role instead.
    </p>

    @foreach ($permissionsByModule as $module => $permissions)
        <div class="card mb-3">
            <div class="card-header text-uppercase small fw-semibold">{{ $module }}</div>
            <div class="table-responsive">
                <table class="table table-compact mb-0">
                    <thead>
                        <tr>
                            <th style="width: 30%">Permission</th>
                            <th>Roles with this permission</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($permissions as $permission)
                            <tr>
                                <td><code>{{ $permission->name }}</code></td>
                                <td>
                                    @forelse ($permission->roles as $role)
                                        <span class="badge text-bg-light border">{{ $role->name }}</span>
                                    @empty
                                        <span class="text-body-secondary">— (Superadmin only, via bypass)</span>
                                    @endforelse
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
@endsection
