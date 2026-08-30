@extends('layouts.guest')

@section('title', 'Two-factor authentication')

@section('content')
    <div x-data="{ useRecoveryCode: false }">
        <h1 class="h5 mb-2">Two-factor authentication</h1>

        <p class="text-body-secondary small" x-show="! useRecoveryCode">
            Enter the code from your authenticator app.
        </p>
        <p class="text-body-secondary small" x-show="useRecoveryCode" x-cloak>
            Enter one of your recovery codes.
        </p>

        <form method="POST" action="{{ route('two-factor.login.store') }}">
            @csrf

            <div class="mb-3" x-show="! useRecoveryCode">
                <label for="code" class="form-label">Authentication code</label>
                <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                       class="form-control @error('code') is-invalid @enderror" autofocus>
                @error('code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3" x-show="useRecoveryCode" x-cloak>
                <label for="recovery_code" class="form-label">Recovery code</label>
                <input id="recovery_code" type="text" name="recovery_code" autocomplete="off"
                       class="form-control @error('recovery_code') is-invalid @enderror">
                @error('recovery_code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-2">Verify</button>

            <button type="button" class="btn btn-link btn-sm w-100" @click="useRecoveryCode = ! useRecoveryCode">
                <span x-text="useRecoveryCode ? 'Use an authentication code instead' : 'Use a recovery code instead'"></span>
            </button>
        </form>
    </div>
@endsection
