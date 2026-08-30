@extends('layouts.admin')

@section('title', 'Add user')

@php($breadcrumbs = [['label' => 'Administration'], ['label' => 'Users', 'url' => route('admin.users.index')], ['label' => 'Add user']])

@section('content')
    <div class="card" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="name">Name</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}"
                           class="form-control @error('name') is-invalid @enderror" required autofocus>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           class="form-control @error('email') is-invalid @enderror" required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">A link to set a password will be emailed to this address.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label d-block">Roles</label>
                    @foreach ($roles as $role)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->name }}"
                                   id="role-{{ $role->id }}" {{ in_array($role->name, old('roles', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="role-{{ $role->id }}">{{ $role->name }}</label>
                        </div>
                    @endforeach
                </div>

                <button type="submit" class="btn btn-primary">Create user</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
