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
