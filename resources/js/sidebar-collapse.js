// Wires up the desktop sidebar's collapse-to-icons toggle. The initial
// (pre-paint) state is set by the inline script in
// resources/views/layouts/partials/head.blade.php to avoid a flash of
// the wrong width — this file only handles the toggle button after load.

const STORAGE_KEY = 'sidebar-collapsed';

const isCollapsed = () => localStorage.getItem(STORAGE_KEY) === 'true';

const applyState = (collapsed) => {
    document.documentElement.setAttribute('data-sidebar', collapsed ? 'collapsed' : 'expanded');

    const toggle = document.querySelector('#sidebar-collapse-toggle');
    if (toggle) {
        toggle.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
        toggle.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        toggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
    }
};

document.addEventListener('DOMContentLoaded', () => {
    applyState(isCollapsed());

    document.querySelector('#sidebar-collapse-toggle')?.addEventListener('click', () => {
        const collapsed = !isCollapsed();
        localStorage.setItem(STORAGE_KEY, String(collapsed));
        applyState(collapsed);
    });
});
