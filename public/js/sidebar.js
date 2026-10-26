document.addEventListener('DOMContentLoaded', function () {

    'use strict';

    if (document.documentElement.dataset.waNavInitialized === 'true') {
        return;
    }
    document.documentElement.dataset.waNavInitialized = 'true';

    // ========================================
    // SIDEBAR & TOPBAR ELEMENTS
    // ========================================

    const sidebar = document.getElementById('appSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const menuButton = document.querySelector('.wa-menu-button');
    const profileToggle = document.getElementById('profileToggle');
    const profileMenu = document.getElementById('profileMenu');

    // ========================================
    // PROFILE DROPDOWN HELPERS
    // ========================================

    function isProfileMenuOpen() {
        return Boolean(profileMenu && !profileMenu.hidden);
    }

    function openProfileMenu() {
        if (!profileMenu || !profileToggle) return;

        profileMenu.hidden = false;
        profileToggle.setAttribute('aria-expanded', 'true');
    }

    function closeProfileMenu(restoreFocus) {
        if (!profileMenu) return;

        const wasOpen = !profileMenu.hidden;
        profileMenu.hidden = true;

        if (profileToggle) {
            profileToggle.setAttribute('aria-expanded', 'false');

            if (restoreFocus && wasOpen) {
                profileToggle.focus();
            }
        }
    }

    function toggleProfileMenu() {
        if (isProfileMenuOpen()) {
            closeProfileMenu(false);
        } else {
            openProfileMenu();
        }
    }

    // ========================================
    // OPEN MOBILE SIDEBAR
    // ========================================

    function openSidebar() {
        if (!sidebar) return;

        closeProfileMenu(false);

        sidebar.classList.add('is-open');

        if (overlay) {
            overlay.classList.add('is-visible');
        }

        if (menuButton) {
            menuButton.setAttribute('aria-expanded', 'true');
        }
    }

    // ========================================
    // CLOSE MOBILE SIDEBAR
    // ========================================

    function closeSidebar(restoreFocus) {
        if (!sidebar) return;

        const wasOpen = sidebar.classList.contains('is-open');
        sidebar.classList.remove('is-open');

        if (overlay) {
            overlay.classList.remove('is-visible');
        }

        if (menuButton) {
            menuButton.setAttribute('aria-expanded', 'false');

            if (restoreFocus && wasOpen) {
                menuButton.focus();
            }
        }
    }

    // ========================================
    // PROFILE DROPDOWN EVENTS
    // ========================================

    if (profileToggle && profileMenu) {
        profileToggle.setAttribute('aria-expanded', String(!profileMenu.hidden));

        profileToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            toggleProfileMenu();
        });

        profileToggle.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                openProfileMenu();
                const firstAction = profileMenu.querySelector('button, a, [tabindex]:not([tabindex="-1"])');
                if (firstAction) {
                    firstAction.focus();
                }
            }
        });

        document.addEventListener('click', function (event) {
            if (!isProfileMenuOpen()) return;

            const target = event.target;
            if (!profileMenu.contains(target) && !profileToggle.contains(target)) {
                closeProfileMenu(false);
            }
        });
    }

    // ========================================
    // MOBILE MENU BUTTON
    // ========================================

    if (menuButton) {
        menuButton.addEventListener('click', function () {

            if (sidebar && sidebar.classList.contains('is-open')) {
                closeSidebar(false);
            } else {
                openSidebar();
            }

        });
    }

    // ========================================
    // SIDEBAR OVERLAY
    // ========================================

    if (overlay) {
        overlay.addEventListener('click', function () {
            closeSidebar(false);
        });
    }

    // ========================================
    // ESCAPE KEY
    // ========================================

    document.addEventListener('keydown', function (event) {

        if (event.key === 'Escape') {
            if (isProfileMenuOpen()) {
                event.preventDefault();
                closeProfileMenu(true);
                return;
            }

            if (sidebar && sidebar.classList.contains('is-open')) {
                closeSidebar(true);
            }
        }

    });

    // ========================================
    // RESPONSIVE SIDEBAR
    // ========================================

    window.addEventListener('resize', function () {

        if (window.innerWidth > 991) {
            closeSidebar(false);
        }

    });

    // ========================================
    // ALL SIDEBAR SUBMENUS
    // Reports + Users & Roles
    // ========================================

    const submenuGroups = document.querySelectorAll('.wa-report-menu');

    submenuGroups.forEach(function (menu) {

        const toggle = menu.querySelector('.wa-report-toggle');
        const submenu = menu.querySelector('.wa-report-submenu');

        if (!toggle || !submenu) return;

        // Keep active submenu open on page load
        const isInitiallyOpen = menu.classList.contains('is-open');

        submenu.hidden = !isInitiallyOpen;

        toggle.setAttribute(
            'aria-expanded',
            String(isInitiallyOpen)
        );

        // Toggle submenu
        toggle.addEventListener('click', function () {

            const isOpen = menu.classList.toggle('is-open');

            submenu.hidden = !isOpen;

            toggle.setAttribute(
                'aria-expanded',
                String(isOpen)
            );

        });

    });

    // ========================================
    // CLOSE MOBILE SIDEBAR AFTER NAVIGATION
    // ========================================

    if (sidebar) {

        sidebar.querySelectorAll('a.wa-nav-link, a.wa-report-sublink')
            .forEach(function (link) {

                link.addEventListener('click', function () {

                    if (window.innerWidth <= 991) {
                        closeSidebar(false);
                    }

                });

            });

    }

    // ========================================
    // CLIENT-SIDE PHONE INPUT ASSISTANCE
    // ========================================

    const phoneInputs = document.querySelectorAll('input[type="tel"][name="phone"]');

    phoneInputs.forEach(function (phoneInput) {
        phoneInput.addEventListener('input', function () {
            const raw = phoneInput.value;
            const hasLeadingPlus = raw.trimStart().startsWith('+');
            const digitsOnly = raw.replace(/[^0-9]/g, '').slice(0, 15);
            const sanitized = (hasLeadingPlus ? '+' : '') + digitsOnly;

            if (phoneInput.value !== sanitized) {
                phoneInput.value = sanitized;
            }
        });
    });

});
