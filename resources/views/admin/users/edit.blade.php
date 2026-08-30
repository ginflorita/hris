@extends('layouts.admin')

@section('title', 'Manage user')

@php($breadcrumbs = [['label' => 'Administration'], ['label' => 'Users', 'url' => route('admin.users.index')], ['label' => $targetUser->name]])

@section('content')
    @session('status')
        <div class="alert alert-success py-2">{{ $value }}</div>
    @endsession

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">Profile</div>
                <div class="card-body">
                    @if ($targetUser->is_protected)
                        <div class="alert alert-secondary py-2 small">
                            This is a protected system account — it can't be disabled and can't lose the
                            Superadmin role.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.users.update', $targetUser) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label" for="name">Name</label>
                            <input id="name" type="text" name="name" value="{{ old('name', $targetUser->name) }}"
                                   class="form-control @error('name') is-invalid @enderror">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="email">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email', $targetUser->email) }}"
                                   class="form-control @error('email') is-invalid @enderror">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    </form>

                    <hr>

                    <div class="d-flex flex-wrap gap-2">
                        @can('resetPassword', $targetUser)
                            <form method="POST" action="{{ route('admin.users.reset-password', $targetUser) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary btn-sm">Send password reset link</button>
                            </form>
                        @endcan

                        @can('forceLogout', $targetUser)
                            <form method="POST" action="{{ route('admin.users.force-logout', $targetUser) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary btn-sm">Log out all sessions</button>
                            </form>
                        @endcan

                        @if ($targetUser->isDisabled())
                            @can('enable', $targetUser)
                                <form method="POST" action="{{ route('admin.users.enable', $targetUser) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-success btn-sm">Enable account</button>
                                </form>
                            @endcan
                        @else
                            @can('disable', $targetUser)
                                <form method="POST" action="{{ route('admin.users.disable', $targetUser) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Disable account</button>
                                </form>
                            @endcan
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">Roles</div>
                <div class="card-body">
                    @can('assignRoles', $targetUser)
                        @error('roles')
                            <div class="alert alert-danger py-2">{{ $message }}</div>
                        @enderror

                        <form method="POST" action="{{ route('admin.users.roles.update', $targetUser) }}">
                            @csrf
                            @method('PUT')

                            @foreach ($roles as $role)
                                @php($isSuperadminRole = $role->name === \App\Enums\DefaultRole::Superadmin->value)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->name }}"
                                           id="role-{{ $role->id }}"
                                           {{ $targetUser->hasRole($role->name) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="role-{{ $role->id }}">
                                        {{ $role->name }}
                                        @if ($isSuperadminRole && $targetUser->is_protected)
                                            <span class="text-body-secondary">(cannot be removed from this account)</span>
                                        @endif
                                    </label>
                                </div>
                            @endforeach

                            <button type="submit" class="btn btn-primary btn-sm mt-2">Save roles</button>
                        </form>
                    @else
                        <ul class="mb-0">
                            @foreach ($targetUser->roles as $role)
                                <li>{{ $role->name }}</li>
                            @endforeach
                        </ul>
                    @endcan
                </div>
            </div>
        </div>
    </div>
@endsection
