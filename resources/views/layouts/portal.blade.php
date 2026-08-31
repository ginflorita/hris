<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head')
</head>
<body>
    @include('layouts.partials.theme-icons')

    <div class="d-flex app-shell">
        @include('layouts.partials.portal-sidebar')

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
