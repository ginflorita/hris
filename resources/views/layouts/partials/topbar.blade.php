<header class="app-topbar d-flex align-items-center gap-2 px-3 py-2">
    <button class="btn btn-outline-secondary d-lg-none" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar"
            aria-label="Toggle navigation">
        <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor">
            <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
        </svg>
    </button>

    {{-- Desktop-only: collapses the fixed sidebar to icons and back — see sidebar.blade.php's own top comment for why this lives here and not in the sidebar itself. --}}
    <button class="btn btn-outline-secondary d-none d-lg-inline-flex" type="button"
            id="sidebar-collapse-toggle" aria-controls="appSidebar" aria-pressed="false"
            aria-label="Collapse sidebar" title="Collapse sidebar">
        <i class="bi bi-chevron-double-left" aria-hidden="true"></i>
    </button>

    <div class="flex-grow-1 fw-semibold">
        @yield('title', 'Dashboard')
    </div>

    @include('layouts.partials.theme-toggle')

    <div class="dropdown">
        <button class="btn btn-outline-secondary d-flex align-items-center gap-2" id="account-menu" type="button"
                data-bs-toggle="dropdown" aria-expanded="false">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.5 6.5c0-2.5 2.5-4.5 5.5-4.5s5.5 2 5.5 4.5"
                      fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="d-none d-sm-inline">{{ auth()->user()?->name }}</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="account-menu">
            <li><a class="dropdown-item" href="{{ route('security.index') }}">Security &amp; sessions</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item">Log out</button>
                </form>
            </li>
        </ul>
    </div>
</header>
