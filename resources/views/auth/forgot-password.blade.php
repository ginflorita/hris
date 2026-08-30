@extends('layouts.guest')

@section('title', 'Forgot password')

@section('content')
    <h1 class="h5 mb-2">Forgot your password?</h1>
    <p class="text-body-secondary small">
        Enter your email and we'll send you a link to reset your password.
    </p>

    @session('status')
        <div class="alert alert-success py-2">{{ $value }}</div>
    @endsession

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   required autofocus autocomplete="username">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-100 mb-2">Email password reset link</button>
        <a href="{{ route('login') }}" class="btn btn-link btn-sm w-100">Back to login</a>
    </form>
@endsection
