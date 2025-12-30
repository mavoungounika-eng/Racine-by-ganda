/**
 * SCRIPT DE PERFECTIONNEMENT FINAL - RACINE BY GANDA
 * Amélioration de l'interactivité, performance et accessibilité
 */

(function() {
    'use strict';
    
    // Configuration
    const CONFIG = {
        lazyLoadOffset: 100,
        animationDuration: 600,
        scrollThrottle: 16,
        enableDebug: false
    };
    
    // Utilitaires
    const utils = {
        log: (message, data = null) => {
            if (CONFIG.enableDebug) {
                console.log(`[PERFECTIONNEMENT] ${message}`, data || '');
            }
        },
        
        throttle: (func, delay) => {
            let timeoutId;
            let lastExecTime = 0;
            return function (...args) {
                const currentTime = Date.now();
                if (currentTime - lastExecTime > delay) {
                    func.apply(this, args);
                    lastExecTime = currentTime;
                } else {
                    clearTimeout(timeoutId);
                    timeoutId = setTimeout(() => {
                        func.apply(this, args);
                        lastExecTime = Date.now();
                    }, delay - (currentTime - lastExecTime));
                }
            };
        },
        
        debounce: (func, delay) => {
            let timeoutId;
            return function (...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => func.apply(this, args), delay);
            };
        }
    };
    
    // Gestionnaire de lazy loading pour les images
    class LazyImageLoader {
        constructor() {
            this.images = [];
            this.observer = null;
            this.init();
        }
        
        init() {
            this.images = document.querySelectorAll('img[data-src], img[loading="lazy"]');
            
            if ('IntersectionObserver' in window) {
                this.setupIntersectionObserver();
            } else {
                this.fallbackLoad();
            }
        }
        
        setupIntersectionObserver() {
            const options = {
                root: null,
                rootMargin: `${CONFIG.lazyLoadOffset}px`,
                threshold: 0.1
            };
            
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadImage(entry.target);
                        this.observer.unobserve(entry.target);
                    }
                });
            }, options);
            
            this.images.forEach(img => this.observer.observe(img));
        }
        
        loadImage(img) {
            if (img.dataset.src) {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
            }
            
            img.addEventListener('load', () => {
                img.classList.add('loaded');
                utils.log('Image chargée:', img.src);
            });
            
            img.addEventListener('error', () => {
                img.classList.add('error');
                utils.log('Erreur de chargement:', img.src);
            });
        }
        
        fallbackLoad() {
            this.images.forEach(img => this.loadImage(img));
        }
    }
    
    // Gestionnaire d'animations au scroll
    class ScrollAnimator {
        constructor() {
            this.elements = [];
            this.init();
        }
        
        init() {
            this.elements = document.querySelectorAll('[data-animate]');
            
            if ('IntersectionObserver' in window) {
                this.setupObserver();
            } else {
                this.fallbackAnimate();
            }
        }
        
        setupObserver() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.animateElement(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });
            
            this.elements.forEach(el => observer.observe(el));
        }
        
        animateElement(element) {
            const animationType = element.dataset.animate || 'fadeInUp';
            const delay = element.dataset.delay || 0;
            
            setTimeout(() => {
                element.classList.add(`animate-${animationType}`);
                utils.log('Animation déclenchée:', animationType);
            }, parseInt(delay));
        }
        
        fallbackAnimate() {
            this.elements.forEach(el => this.animateElement(el));
        }
    }
    
    // Gestionnaire de smooth scroll
    class SmoothScroller {
        constructor() {
            this.init();
        }
        
        init() {
            document.querySelectorAll('a[href^="#"]').forEach(link => {
                link.addEventListener('click', this.handleClick.bind(this));
            });
        }
        
        handleClick(e) {
            const href = e.currentTarget.getAttribute('href');
            if (href === '#') return;
            
            const target = document.querySelector(href);
            if (!target) return;
            
            e.preventDefault();
            
            const offsetTop = target.offsetTop - 80; // Offset pour la navbar
            
            window.scrollTo({
                top: offsetTop,
                behavior: 'smooth'
            });
            
            // Mettre le focus sur l'élément cible pour l'accessibilité
            target.focus();
            utils.log('Scroll vers:', href);
        }
    }
    
    // Gestionnaire d'améliorations des formulaires
    class FormEnhancer {
        constructor() {
            this.forms = [];
            this.init();
        }
        
        init() {
            this.forms = document.querySelectorAll('form');
            this.forms.forEach(form => this.enhanceForm(form));
        }
        
        enhanceForm(form) {
            // Ajouter la validation en temps réel
            const inputs = form.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                input.addEventListener('blur', () => this.validateField(input));
                input.addEventListener('input', utils.debounce(() => this.validateField(input), 300));
            });
            
            // Améliorer la soumission
            form.addEventListener('submit', (e) => this.handleSubmit(e, form));
        }
        
        validateField(field) {
            const isValid = field.checkValidity();
            field.classList.toggle('is-valid', isValid);
            field.classList.toggle('is-invalid', !isValid);
            
            // Annoncer les erreurs aux lecteurs d'écran
            if (!isValid && field.validationMessage) {
                this.announceError(field, field.validationMessage);
            }
        }
        
        announceError(field, message) {
            let errorElement = field.parentNode.querySelector('.error-message');
            if (!errorElement) {
                errorElement = document.createElement('div');
                errorElement.className = 'error-message';
                errorElement.setAttribute('role', 'alert');
                field.parentNode.appendChild(errorElement);
            }
            errorElement.textContent = message;
        }
        
        handleSubmit(e, form) {
            // Valider tout le formulaire
            const isValid = form.checkValidity();
            if (!isValid) {
                e.preventDefault();
                utils.log('Formulaire invalide');
            } else {
                utils.log('Formulaire soumis');
            }
        }
    }
    
    // Gestionnaire de performance
    class PerformanceOptimizer {
        constructor() {
            this.init();
        }
        
        init() {
            this.optimizeImages();
            this.preloadCriticalResources();
            this.setupServiceWorker();
        }
        
        optimizeImages() {
            // Lazy loading pour toutes les images non critiques
            document.querySelectorAll('img').forEach(img => {
                if (!img.hasAttribute('loading')) {
                    img.setAttribute('loading', 'lazy');
                }
            });
        }
        
        preloadCriticalResources() {
            // Précharger les ressources critiques
            const criticalResources = [
                'css/style.css',
                'css/perfectionnement.css',
                'js/main.js'
            ];
            
            criticalResources.forEach(resource => {
                const link = document.createElement('link');
                link.rel = 'preload';
                link.href = resource;
                link.as = resource.endsWith('.css') ? 'style' : 'script';
                document.head.appendChild(link);
            });
        }
        
        setupServiceWorker() {
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        utils.log('Service Worker enregistré:', registration.scope);
                    })
                    .catch(error => {
                        utils.log('Erreur Service Worker:', error);
                    });
            }
        }
    }
    
    // Gestionnaire d'accessibilité avancée
    class AccessibilityEnhancer {
        constructor() {
            this.init();
        }
        
        init() {
            this.addSkipLinks();
            this.enhanceKeyboardNavigation();
            this.addAriaLabels();
            this.setupFocusManagement();
        }
        
        addSkipLinks() {
            // Les skip links sont déjà dans le HTML
            const skipLink = document.querySelector('.skip-link');
            if (skipLink) {
                skipLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    const target = document.querySelector(skipLink.getAttribute('href'));
                    if (target) {
                        target.focus();
                        target.scrollIntoView();
                    }
                });
            }
        }
        
        enhanceKeyboardNavigation() {
            // Navigation au clavier pour les cartes produits
            document.querySelectorAll('.product').forEach(product => {
                product.setAttribute('tabindex', '0');
                product.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        const link = product.querySelector('a');
                        if (link) link.click();
                    }
                });
            });
        }
        
        addAriaLabels() {
            // Ajouter des labels ARIA manquants
            document.querySelectorAll('button, a').forEach(element => {
                if (!element.getAttribute('aria-label') && !element.textContent.trim()) {
                    const icon = element.querySelector('[class*="icon-"]');
                    if (icon) {
                        const iconClass = Array.from(icon.classList).find(cls => cls.startsWith('icon-'));
                        if (iconClass) {
                            element.setAttribute('aria-label', this.getIconLabel(iconClass));
                        }
                    }
                }
            });
        }
        
        getIconLabel(iconClass) {
            const labels = {
                'icon-shopping_cart': 'Panier',
                'icon-instagram': 'Instagram',
                'icon-facebook': 'Facebook',
                'icon-twitter': 'Twitter',
                'icon-phone2': 'Téléphone',
                'icon-paper-plane': 'Email',
                'icon-map-marker': 'Adresse'
            };
            return labels[iconClass] || 'Bouton';
        }
        
        setupFocusManagement() {
            // Gérer le focus pour les modales et overlays
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    // Logique de piège de focus si nécessaire
                }
                
                if (e.key === 'Escape') {
                    // Fermer les modales ouvertes
                    const openModals = document.querySelectorAll('.modal.show, .popup.active');
                    openModals.forEach(modal => {
                        if (modal.style.display !== 'none') {
                            modal.style.display = 'none';
                        }
                    });
                }
            });
        }
    }
    
    // Gestionnaire principal
    class MainController {
        constructor() {
            this.components = [];
            this.init();
        }
        
        init() {
            utils.log('Initialisation du perfectionnement');
            
            // Attendre que le DOM soit prêt
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.initComponents());
            } else {
                this.initComponents();
            }
            
            // Initialiser après le chargement complet
            if (document.readyState !== 'complete') {
                window.addEventListener('load', () => this.initPostLoad());
            } else {
                this.initPostLoad();
            }
        }
        
        initComponents() {
            try {
                this.components.push(new LazyImageLoader());
                this.components.push(new ScrollAnimator());
                this.components.push(new SmoothScroller());
                this.components.push(new FormEnhancer());
                this.components.push(new AccessibilityEnhancer());
                
                utils.log('Composants initialisés');
            } catch (error) {
                console.error('Erreur lors de l\'initialisation:', error);
            }
        }
        
        initPostLoad() {
            try {
                this.components.push(new PerformanceOptimizer());
                utils.log('Optimisations post-chargement appliquées');
            } catch (error) {
                console.error('Erreur lors de l\'optimisation:', error);
            }
        }
    }
    
    // Initialisation
    new MainController();
    
    // Exposer l'API globale
    window.RacineGandaPerfect = {
        utils,
        config: CONFIG,
        reload: () => new MainController()
    };
    
    utils.log('Script de perfectionnement chargé');
    
})();
