<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>

    {{-- Set the theme before first paint to avoid a flash of the wrong mode. --}}
    <script>
        (() => {
            const stored = localStorage.getItem('theme');
            const theme = stored === 'light' || stored === 'dark'
                ? stored
                : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    {{-- Theme toggle icons, referenced by <use> from the topbar. --}}
    <svg xmlns="http://www.w3.org/2000/svg" class="d-none">
        <symbol id="theme-icon-light" viewBox="0 0 16 16">
            <circle cx="8" cy="8" r="3.5"/>
            <g stroke="currentColor" stroke-width="1.2" stroke-linecap="round">
                <line x1="8" y1="0.5" x2="8" y2="2.2"/>
                <line x1="8" y1="13.8" x2="8" y2="15.5"/>
                <line x1="0.5" y1="8" x2="2.2" y2="8"/>
                <line x1="13.8" y1="8" x2="15.5" y2="8"/>
                <line x1="2.6" y1="2.6" x2="3.8" y2="3.8"/>
                <line x1="12.2" y1="12.2" x2="13.4" y2="13.4"/>
                <line x1="2.6" y1="13.4" x2="3.8" y2="12.2"/>
                <line x1="12.2" y1="3.8" x2="13.4" y2="2.6"/>
            </g>
        </symbol>
        <symbol id="theme-icon-dark" viewBox="0 0 16 16">
            <path d="M8,1.5 A7,7 0 1,0 8,14.5 A5,5 0 0,1 8,1.5 Z"/>
        </symbol>
        <symbol id="theme-icon-auto" viewBox="0 0 16 16">
            <path d="M8 1a7 7 0 1 0 0 14V1Z"/>
            <circle cx="8" cy="8" r="6.5" fill="none" stroke="currentColor"/>
        </symbol>
    </svg>

    <div class="d-flex app-shell">
        @include('layouts.partials.sidebar')

        <div class="app-main d-flex flex-column">
            @include('layouts.partials.topbar')

            <main class="flex-grow-1 p-3 p-lg-4">
                @isset($breadcrumbs)
                    <x-breadcrumbs :items="$breadcrumbs" />
                @endisset

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
