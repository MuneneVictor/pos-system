(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const body = document.body;
        const toggle = document.querySelector('[data-sidebar-toggle]');
        const backdrop = document.querySelector('[data-sidebar-backdrop]');

        if (toggle) {
            toggle.addEventListener('click', function () {
                body.classList.toggle('sidebar-open');
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', function () {
                body.classList.remove('sidebar-open');
            });
        }

        document.querySelectorAll('[data-alert-close]').forEach(function (button) {
            button.addEventListener('click', function () {
                const alert = button.closest('.app-alert');
                if (alert) {
                    alert.remove();
                }
            });
        });
    });
})();
