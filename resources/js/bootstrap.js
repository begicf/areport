window._ = require('lodash');
window.bootstrap = require('bootstrap');


/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

document.addEventListener('DOMContentLoaded', function () {
    function closeManualDropdowns(exceptToggle) {
        document.querySelectorAll('.nav-item.dropdown .dropdown-menu.show').forEach(function (menu) {
            const parentDropdown = menu.closest('.nav-item.dropdown');
            const toggle = parentDropdown ? parentDropdown.querySelector('[data-bs-toggle="dropdown"]') : null;

            if (exceptToggle && toggle === exceptToggle) {
                return;
            }

            menu.classList.remove('show');

            if (toggle) {
                toggle.classList.remove('show');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function (element) {
        if (window.bootstrap && typeof window.bootstrap.Dropdown !== 'undefined') {
            window.bootstrap.Dropdown.getOrCreateInstance(element);
        }

        element.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            const dropdown = element.closest('.nav-item.dropdown, .dropdown');
            const menu = dropdown ? dropdown.querySelector('.dropdown-menu') : null;

            if (!menu) {
                return;
            }

            if (window.bootstrap && typeof window.bootstrap.Dropdown !== 'undefined') {
                closeManualDropdowns(element);
                window.bootstrap.Dropdown.getOrCreateInstance(element).toggle();
                return;
            }

            const isOpen = menu.classList.contains('show');

            closeManualDropdowns(element);

            if (!isOpen) {
                menu.classList.add('show');
                element.classList.add('show');
                element.setAttribute('aria-expanded', 'true');
            }
        });
    });

    document.addEventListener('click', function () {
        closeManualDropdowns();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeManualDropdowns();
        }
    });
});

function cleanupModalArtifacts(element) {
    if (!element) {
        return;
    }

    element.classList.remove('show');
    element.style.display = 'none';
    element.setAttribute('aria-hidden', 'true');
    element.removeAttribute('aria-modal');
    element.removeAttribute('role');

    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('padding-right');
    document.body.style.removeProperty('paddingRight');

    document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
        backdrop.remove();
    });
}

window.appModal = {
    show(id) {
        const element = document.getElementById(id);

        if (!element) {
            return;
        }

        cleanupModalArtifacts(element);

        if (typeof bootstrap === 'undefined' || typeof bootstrap.Modal === 'undefined') {
            element.style.display = 'block';
            element.classList.add('show');
            document.body.classList.add('modal-open');
            return;
        }

        bootstrap.Modal.getOrCreateInstance(element).show();
    },

    hide(id) {
        const element = document.getElementById(id);

        if (!element) {
            return;
        }

        if (typeof bootstrap === 'undefined' || typeof bootstrap.Modal === 'undefined') {
            cleanupModalArtifacts(element);
            return;
        }

        const instance = bootstrap.Modal.getInstance(element) || bootstrap.Modal.getOrCreateInstance(element);

        element.addEventListener('hidden.bs.modal', function handleHidden() {
            cleanupModalArtifacts(element);
            element.removeEventListener('hidden.bs.modal', handleHidden);
        });

        instance.hide();

        window.setTimeout(function () {
            cleanupModalArtifacts(element);
        }, 350);
    }
};

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// window.Pusher = require('pusher-js');

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: process.env.MIX_PUSHER_APP_KEY,
//     cluster: process.env.MIX_PUSHER_APP_CLUSTER,
//     encrypted: true
// });
