// Wires up the light/dark/system theme switcher in the topbar.
// The initial (pre-paint) theme is set by a small inline script in
// resources/views/layouts/admin.blade.php to avoid a flash of the
// wrong theme — this file only handles the dropdown UI after load.

const getStoredTheme = () => localStorage.getItem('theme');
const setStoredTheme = (theme) => localStorage.setItem('theme', theme);

const getPreferredTheme = () => {
    const storedTheme = getStoredTheme();
    if (storedTheme) {
        return storedTheme;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
};

const setTheme = (theme) => {
    const resolved = theme === 'auto'
        ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
        : theme;

    document.documentElement.setAttribute('data-bs-theme', resolved);
};

const showActiveTheme = (theme, focus = false) => {
    const themeSwitcher = document.querySelector('#theme-toggle');

    if (!themeSwitcher) {
        return;
    }

    const activeThemeIcon = document.querySelector('.theme-icon-active use');
    const btnToActive = document.querySelector(`[data-bs-theme-value="${theme}"]`);

    if (!btnToActive) {
        return;
    }

    const svgOfActiveBtn = btnToActive.querySelector('svg use').getAttribute('href');

    document.querySelectorAll('[data-bs-theme-value]').forEach((element) => {
        element.classList.remove('active');
        element.setAttribute('aria-pressed', 'false');
    });

    btnToActive.classList.add('active');
    btnToActive.setAttribute('aria-pressed', 'true');
    activeThemeIcon.setAttribute('href', svgOfActiveBtn);

    if (focus) {
        themeSwitcher.focus();
    }
};

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    const storedTheme = getStoredTheme();
    if (storedTheme !== 'light' && storedTheme !== 'dark') {
        setTheme(getPreferredTheme());
    }
});

document.addEventListener('DOMContentLoaded', () => {
    showActiveTheme(getPreferredTheme());

    document.querySelectorAll('[data-bs-theme-value]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const theme = toggle.getAttribute('data-bs-theme-value');
            setStoredTheme(theme);
            setTheme(theme);
            showActiveTheme(theme, true);
        });
    });
});
