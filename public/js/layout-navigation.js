/* =============================================
   🎨 RACINE BY GANDA - NAVIGATION JAVASCRIPT
   Premium Modern Interaction Logic
   ============================================= */

(function () {
    'use strict';

    // ===== NAVBAR SCROLL EFFECT =====
    const handleScroll = () => {
        const header = document.querySelector('.navbar-racine');
        if (header) {
            if (window.scrollY > 30) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        }
    };

    window.addEventListener('scroll', handleScroll);
    // Initial check
    handleScroll();

    // ===== MOBILE MENU TOGGLE =====
    const toggle = document.getElementById('mobile-menu-toggle');
    const menu = document.getElementById('mobile-menu');

    if (toggle && menu) {
        toggle.addEventListener('click', () => {
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            if (!isExpanded) {
                menu.style.maxHeight = '800px';
                toggle.setAttribute('aria-expanded', 'true');
                toggle.setAttribute('aria-label', 'Fermer le menu mobile');
            } else {
                menu.style.maxHeight = '0px';
                toggle.setAttribute('aria-expanded', 'false');
                toggle.setAttribute('aria-label', 'Ouvrir le menu mobile');
            }
        });

        // Close menu on click outside
        document.addEventListener('click', (e) => {
            if (!toggle.contains(e.target) && !menu.contains(e.target)) {
                menu.style.maxHeight = '0px';
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // ===== DROPDOWN NAVIGATION =====
    const dropdowns = document.querySelectorAll('.nav-dropdown');

    dropdowns.forEach(dropdown => {
        const dToggle = dropdown.querySelector('.nav-dropdown-toggle');

        if (dToggle) {
            dToggle.addEventListener('click', (e) => {
                const isMobile = window.innerWidth < 992;
                if (isMobile) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropdown.classList.toggle('active');
                }
            });
        }
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        dropdowns.forEach(d => {
            if (!d.contains(e.target)) {
                d.classList.remove('active');
            }
        });
    });

    // Close menu and dropdowns with Escape (accessibility)
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;

        if (toggle && menu) {
            menu.style.maxHeight = '0px';
            toggle.setAttribute('aria-expanded', 'false');
        }

        dropdowns.forEach(d => d.classList.remove('active'));
    });
})();
