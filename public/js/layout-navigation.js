/* =============================================
   🎨 RACINE BY GANDA - NAVIGATION JAVASCRIPT
   Extrait du layout frontend pour optimisation
   ============================================= */

(function() {
    'use strict';

    // ===== NAVBAR SCROLL EFFECT =====
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('header');
        if (navbar && window.scrollY > 50) {
            navbar.style.background = 'linear-gradient(135deg, #1c1412 0%, #261915 100%)';
        } else if (navbar) {
            navbar.style.background = 'linear-gradient(135deg, rgba(28, 20, 18, 0.98) 0%, rgba(38, 25, 21, 0.95) 100%)';
        }
    });

    // ===== MOBILE MENU TOGGLE =====
    const toggle = document.getElementById('mobile-menu-toggle');
    const menu = document.getElementById('mobile-menu');
    
    if (toggle && menu) {
        toggle.addEventListener('click', () => {
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            if (!isExpanded) {
                menu.style.maxHeight = '600px';
                toggle.setAttribute('aria-expanded', 'true');
                toggle.setAttribute('aria-label', 'Fermer le menu mobile');
            } else {
                menu.style.maxHeight = '0px';
                toggle.setAttribute('aria-expanded', 'false');
                toggle.setAttribute('aria-label', 'Ouvrir le menu mobile');
            }
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!toggle.contains(e.target) && !menu.contains(e.target) && menu.style.maxHeight !== '0px') {
                menu.style.maxHeight = '0px';
                toggle.setAttribute('aria-expanded', 'false');
                toggle.setAttribute('aria-label', 'Ouvrir le menu mobile');
            }
        });
        
        // Close mobile menu with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && menu.style.maxHeight !== '0px') {
                menu.style.maxHeight = '0px';
                toggle.setAttribute('aria-expanded', 'false');
                toggle.setAttribute('aria-label', 'Ouvrir le menu mobile');
                toggle.focus();
            }
        });
    }

    // ===== DROPDOWN NAVIGATION =====
    const dropdowns = document.querySelectorAll('.nav-dropdown');
    
    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.nav-dropdown-toggle');
        const menu = dropdown.querySelector('.nav-dropdown-menu');
        
        // Click toggle pour mobile et desktop
        if (toggle) {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const isActive = dropdown.classList.contains('active');
                
                // Fermer les autres dropdowns
                dropdowns.forEach(d => {
                    if (d !== dropdown) {
                        d.classList.remove('active');
                        const otherToggle = d.querySelector('.nav-dropdown-toggle');
                        if (otherToggle) {
                            otherToggle.setAttribute('aria-expanded', 'false');
                        }
                    }
                });
                
                // Toggle le dropdown actuel
                dropdown.classList.toggle('active');
                toggle.setAttribute('aria-expanded', !isActive ? 'true' : 'false');
            });
        }
    });
    
    // Fermer les dropdowns en cliquant ailleurs
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.nav-dropdown')) {
            dropdowns.forEach(d => {
                d.classList.remove('active');
                const toggle = d.querySelector('.nav-dropdown-toggle');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        }
    });
    
    // Fermer les dropdowns avec Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            dropdowns.forEach(d => {
                d.classList.remove('active');
                const toggle = d.querySelector('.nav-dropdown-toggle');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.focus();
                }
            });
        }
    });
})();

