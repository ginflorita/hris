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
