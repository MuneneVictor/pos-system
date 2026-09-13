(function () {
    'use strict';

    const STORAGE_KEY = 'pos-theme';
    const ALLOWED_THEMES = ['light', 'dark'];

    function getLocalTheme() {
        try {
            const stored = localStorage.getItem(STORAGE_KEY);
            return ALLOWED_THEMES.includes(stored) ? stored : null;
        } catch (error) {
            return null;
        }
    }

    function initialTheme() {
        const accountTheme = document.documentElement.dataset.userTheme;

        if (ALLOWED_THEMES.includes(accountTheme)) {
            return accountTheme;
        }

        return getLocalTheme() || 'light';
    }

    function applyTheme(theme) {
        const safeTheme = ALLOWED_THEMES.includes(theme) ? theme : 'light';
        document.documentElement.setAttribute('data-theme', safeTheme);

        document.querySelectorAll('[data-theme-icon]').forEach(function (icon) {
            icon.textContent = safeTheme === 'dark' ? '☀' : '☾';
        });

        try {
            localStorage.setItem(STORAGE_KEY, safeTheme);
        } catch (error) {
            // Theme still works when storage is disabled.
        }
    }

    async function persistAccountTheme(theme) {
        const authMeta = document.querySelector('meta[name="app-authenticated"]');
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');

        if (!authMeta || authMeta.content !== '1' || !csrfMeta) {
            return;
        }

        try {
            await fetch(window.APP_THEME_ENDPOINT || 'auth/theme.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    _csrf: csrfMeta.content,
                    theme: theme
                })
            });
        } catch (error) {
            // Local theme remains usable even if persistence temporarily fails.
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        let activeTheme = initialTheme();
        applyTheme(activeTheme);

        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                activeTheme = activeTheme === 'dark' ? 'light' : 'dark';
                applyTheme(activeTheme);
                persistAccountTheme(activeTheme);

                document.dispatchEvent(new CustomEvent('app:theme-changed', {
                    detail: { theme: activeTheme }
                }));
            });
        });
    });
})();
