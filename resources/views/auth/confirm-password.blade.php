@extends('layouts.guest')

@section('title', 'Confirm password')

@section('content')
    <h1 class="h5 mb-2">Confirm your password</h1>
    <p class="text-body-secondary small">
        This is a sensitive action — please confirm your password before continuing.
    </p>

    <form method="POST" action="{{ route('password.confirm.store') }}">
        @csrf

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   required autofocus autocomplete="current-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-100">Confirm</button>
    </form>
@endsection
