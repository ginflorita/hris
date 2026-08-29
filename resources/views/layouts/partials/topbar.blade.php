<header class="app-topbar d-flex align-items-center gap-2 px-3 py-2">
    <button class="btn btn-outline-secondary d-lg-none" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar"
            aria-label="Toggle navigation">
        <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor">
            <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
        </svg>
    </button>

    <div class="flex-grow-1 fw-semibold">
        @yield('title', 'Dashboard')
    </div>

    <div class="dropdown">
        <button class="btn btn-outline-secondary d-flex align-items-center" id="theme-toggle" type="button"
                data-bs-toggle="dropdown" aria-expanded="false" aria-label="Toggle theme">
            <svg class="theme-icon-active" width="16" height="16" fill="currentColor">
                <use href="#theme-icon-light"></use>
            </svg>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="theme-toggle">
            <li>
                <button type="button" class="dropdown-item d-flex align-items-center gap-2 active"
                        data-bs-theme-value="light" aria-pressed="true">
                    <svg width="16" height="16" fill="currentColor"><use href="#theme-icon-light"></use></svg>
                    Light
                </button>
            </li>
            <li>
                <button type="button" class="dropdown-item d-flex align-items-center gap-2"
                        data-bs-theme-value="dark" aria-pressed="false">
                    <svg width="16" height="16" fill="currentColor"><use href="#theme-icon-dark"></use></svg>
                    Dark
                </button>
            </li>
            <li>
                <button type="button" class="dropdown-item d-flex align-items-center gap-2"
                        data-bs-theme-value="auto" aria-pressed="false">
                    <svg width="16" height="16" fill="currentColor"><use href="#theme-icon-auto"></use></svg>
                    System
                </button>
            </li>
        </ul>
    </div>
</header>
