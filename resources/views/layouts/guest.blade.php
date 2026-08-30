<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head')
</head>
<body>
    @include('layouts.partials.theme-icons')

    <div class="guest-shell d-flex flex-column align-items-center justify-content-center min-vh-100 p-3">
        <div class="position-absolute top-0 end-0 p-3">
            @include('layouts.partials.theme-toggle')
        </div>

        <div class="w-100" style="max-width: 400px;">
            <div class="text-center mb-4">
                <span class="fs-4 fw-semibold">{{ config('app.name') }}</span>
            </div>

            <div class="card">
                <div class="card-body p-4">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
</body>
</html>
