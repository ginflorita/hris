@extends('layouts.admin')

@section('title', 'Users')

@php($breadcrumbs = [['label' => 'Administration'], ['label' => 'Users']])

@section('content')
    @session('status')
        <div class="alert alert-success py-2">{{ $value }}</div>
    @endsession

    <div class="d-flex align-items-center justify-content-between mb-3">
        <form method="GET" class="d-flex" style="max-width: 320px;">
            <input type="search" name="q" value="{{ $search }}" placeholder="Search name or email"
                   class="form-control form-control-sm">
        </form>

        @can('create', App\Models\User::class)
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm text-nowrap ms-2">Add user</a>
        @endcan
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-compact mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Roles</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                @can('view', $user)
                                    <a href="{{ route('admin.users.edit', $user) }}">{{ $user->name }}</a>
                                @else
                                    {{ $user->name }}
                                @endcan
                                @if ($user->is_protected)
                                    <span class="badge text-bg-secondary">Protected</span>
                                @endif
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @foreach ($user->roles as $role)
                                    <span class="badge text-bg-light border">{{ $role->name }}</span>
                                @endforeach
                            </td>
                            <td>
                                @if ($user->isDisabled())
                                    <span class="badge text-bg-danger">Disabled</span>
                                @else
                                    <span class="badge text-bg-success">Active</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @can('view', $user)
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">Manage</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-body-secondary py-3">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $users->links() }}
    </div>
@endsection
