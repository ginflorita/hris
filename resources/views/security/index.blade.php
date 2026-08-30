@extends('layouts.admin')

@section('title', 'Security & Sessions')

@php($breadcrumbs = [['label' => 'Security & Sessions']])

@section('content')
    @session('status')
        <div class="alert alert-success py-2">
            @switch($value)
                @case('password-updated') Password updated. @break
                @case('two-factor-authentication-enabled') Scan the QR code below to finish setting up two-factor authentication. @break
                @case('two-factor-authentication-confirmed') Two-factor authentication is now active. @break
                @case('two-factor-authentication-disabled') Two-factor authentication has been disabled. @break
                @case('recovery-codes-generated') New recovery codes generated — save them somewhere safe. @break
                @default {{ $value }}
            @endswitch
        </div>
    @endsession

    @if ($errors->has('session'))
        <div class="alert alert-danger py-2">{{ $errors->first('session') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">Change password</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user-password.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label" for="current_password">Current password</label>
                            <input id="current_password" type="password" name="current_password" autocomplete="current-password"
                                   class="form-control @error('current_password', 'updatePassword') is-invalid @enderror">
                            @error('current_password', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="password">New password</label>
                            <input id="password" type="password" name="password" autocomplete="new-password"
                                   class="form-control @error('password', 'updatePassword') is-invalid @enderror">
                            @error('password', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="password_confirmation">Confirm new password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation"
                                   autocomplete="new-password" class="form-control">
                        </div>

                        <button type="submit" class="btn btn-primary">Update password</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">Two-factor authentication</div>
                <div class="card-body">
                    @if ($twoFactorEnabled)
                        <p class="text-success mb-3">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" class="me-1">
                                <path d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm3.4-9.4-4 4a.5.5 0 0 1-.7 0l-2-2a.5.5 0 1 1 .7-.7L7 8.4l3.6-3.6a.5.5 0 0 1 .8.6Z"/>
                            </svg>
                            Enabled
                        </p>

                        <div x-data="{ show: false }" class="mb-3">
                            <p class="small text-body-secondary mb-2">
                                Recovery codes let you sign in if you lose access to your authenticator app.
                                Each code can only be used once.
                            </p>
                            <button type="button" class="btn btn-sm btn-outline-secondary mb-2" @click="show = ! show">
                                <span x-text="show ? 'Hide recovery codes' : 'Show recovery codes'"></span>
                            </button>
                            <pre class="bg-body-tertiary p-2 rounded small mb-2" x-show="show" x-cloak>{{ implode(PHP_EOL, $recoveryCodes ?? []) }}</pre>
                            <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Regenerate recovery codes</button>
                            </form>
                        </div>

                        <form method="POST" action="{{ route('two-factor.disable') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">Disable two-factor authentication</button>
                        </form>
                    @elseif ($twoFactorPending)
                        <p class="mb-2">Scan this QR code with your authenticator app, then enter the 6-digit code to confirm.</p>

                        <div class="mb-3 bg-white p-2 rounded d-inline-block">
                            {!! $qrCodeSvg !!}
                        </div>

                        <form method="POST" action="{{ route('two-factor.confirm') }}" class="mb-2">
                            @csrf
                            <div class="mb-2" style="max-width: 200px;">
                                <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                                       class="form-control @error('code', 'confirmTwoFactorAuthentication') is-invalid @enderror"
                                       placeholder="123456">
                                @error('code', 'confirmTwoFactorAuthentication')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Confirm</button>
                        </form>

                        <form method="POST" action="{{ route('two-factor.disable') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-link btn-sm p-0">Cancel setup</button>
                        </form>
                    @else
                        <p class="text-body-secondary small mb-3">
                            Two-factor authentication is off. Enable it to require a code from an authenticator
                            app in addition to your password.
                        </p>
                        <form method="POST" action="{{ route('two-factor.enable') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">Enable two-factor authentication</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    Active sessions
                    <form method="POST" action="{{ route('security.sessions.logout-other') }}" class="d-flex gap-2"
                          x-data="{ open: false }">
                        <template x-if="! open">
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click.prevent="open = true">
                                Log out other sessions
                            </button>
                        </template>
                        <template x-if="open">
                            <div class="d-flex gap-2">
                                @csrf
                                <input type="password" name="password" placeholder="Current password"
                                       class="form-control form-control-sm" required>
                                <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap">Confirm</button>
                            </div>
                        </template>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-compact mb-0">
                        <thead>
                            <tr>
                                <th>Device</th>
                                <th>IP address</th>
                                <th>Last active</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sessions as $session)
                                <tr>
                                    <td>
                                        {{ $session->user_agent ? \Illuminate\Support\Str::limit($session->user_agent, 60) : 'Unknown device' }}
                                        @if ($session->is_current_device)
                                            <span class="badge text-bg-success">This device</span>
                                        @endif
                                    </td>
                                    <td>{{ $session->ip_address }}</td>
                                    <td>{{ $session->last_active->diffForHumans() }}</td>
                                    <td class="text-end">
                                        @unless ($session->is_current_device)
                                            <form method="POST" action="{{ route('security.sessions.destroy', $session->id) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Log out</button>
                                            </form>
                                        @endunless
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
