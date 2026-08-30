@extends('layouts.admin')

@section('title', 'Roles')

@php($breadcrumbs = [['label' => 'Administration'], ['label' => 'Roles']])

@section('content')
    @session('status')
        <div class="alert alert-success py-2">{{ $value }}</div>
    @endsession

    @if ($errors->has('role'))
        <div class="alert alert-danger py-2">{{ $errors->first('role') }}</div>
    @endif

    <div class="d-flex justify-content-end mb-3">
        @can('create', App\Models\Role::class)
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm">Add role</a>
        @endcan
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-compact mb-0">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Data scope</th>
                        <th>Permissions</th>
                        <th>Members</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr>
                            <td>
                                {{ $role->name }}
                                @if ($role->name === \App\Enums\DefaultRole::Superadmin->value)
                                    <span class="badge text-bg-secondary">System role</span>
                                @endif
                            </td>
                            <td><span class="badge text-bg-light border">{{ $role->data_scope->value }}</span></td>
                            <td>{{ $role->permissions_count }}</td>
                            <td>{{ $role->users_count }}</td>
                            <td class="text-end">
                                @can('update', $role)
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                @endcan
                                @can('delete', $role)
                                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="d-inline"
                                          onsubmit="return confirm('Delete the {{ $role->name }} role? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
