/**
 * Script d'amélioration pour la section témoignage - RACINE BY GANDA
 * Gestion des animations, interactions et accessibilité
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // S'assurer que tous les éléments sont visibles d'abord
    ensureVisibility();
    
    // Configuration du carousel témoignage amélioré
    if (typeof $ !== 'undefined' && $.fn.owlCarousel) {
        $('.carousel-testimony').owlCarousel({
            loop: true,
            margin: 30,
            nav: false,
            dots: true,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            smartSpeed: 800,
            responsive: {
                0: {
                    items: 1
                },
                600: {
                    items: 1
                },
                1000: {
                    items: 2
                }
            },
            onInitialized: function() {
                // Animation d'apparition des témoignages
                animateTestimonies();
            },
            onChanged: function() {
                // Re-animer lors du changement
                animateTestimonies();
            }
        });
    } else {
        // Fallback si Owl Carousel n'est pas disponible
        console.warn('Owl Carousel non disponible - utilisation du mode fallback');
        setupFallbackCarousel();
    }
    
    // Fonction pour s'assurer que les éléments sont visibles
    function ensureVisibility() {
        const elements = document.querySelectorAll('.services-2, .testimony-wrap, .carousel-testimony, .services-flow');
        elements.forEach(element => {
            element.style.display = 'block';
            element.style.visibility = 'visible';
            element.style.opacity = '1';
        });
        
        // Assurer que les items du carousel sont visibles
        const carouselItems = document.querySelectorAll('.carousel-testimony .item');
        carouselItems.forEach(item => {
            item.style.display = 'block';
            item.style.visibility = 'visible';
            item.style.opacity = '1';
        });
    }
    
    // Configuration fallback pour le carousel
    function setupFallbackCarousel() {
        const carousel = document.querySelector('.carousel-testimony');
        if (carousel) {
            carousel.style.display = 'block';
            const items = carousel.querySelectorAll('.item');
            items.forEach((item, index) => {
                item.style.display = 'block';
                item.style.marginBottom = '30px';
                item.style.opacity = '1';
                item.style.visibility = 'visible';
            });
        }
    }
    
    // Animation des étoiles
    function animateStars() {
        const starRatings = document.querySelectorAll('.star-rating');
        
        starRatings.forEach(rating => {
            const stars = rating.querySelectorAll('i');
            
            // Animation d'apparition séquentielle des étoiles
            stars.forEach((star, index) => {
                star.style.animationDelay = `${index * 0.1}s`;
                star.classList.add('star-appear');
            });
            
            // Animation au hover
            rating.addEventListener('mouseenter', () => {
                stars.forEach((star, index) => {
                    setTimeout(() => {
                        star.classList.add('star-bounce');
                    }, index * 50);
                });
            });
            
            rating.addEventListener('mouseleave', () => {
                stars.forEach(star => {
                    star.classList.remove('star-bounce');
                });
            });
        });
    }
    
    // Animation des témoignages
    function animateTestimonies() {
        const testimonies = document.querySelectorAll('.testimony-wrap');
        
        testimonies.forEach((testimony, index) => {
            // S'assurer que l'élément est visible d'abord
            testimony.style.display = 'block';
            testimony.style.visibility = 'visible';
            testimony.style.opacity = '1';
            
            testimony.style.animationDelay = `${index * 0.2}s`;
            testimony.classList.add('testimony-appear');
        });
    }
    
    // Animation des services
    function animateServices() {
        const services = document.querySelectorAll('.services-2');
        
        services.forEach((service, index) => {
            // S'assurer que l'élément est visible d'abord
            service.style.display = 'flex';
            service.style.visibility = 'visible';
            service.style.opacity = '1';
            
            service.style.animationDelay = `${index * 0.15}s`;
            service.classList.add('service-appear');
        });
    }
    
    // Observer pour les animations au scroll
    function setupScrollAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    if (entry.target.classList.contains('testimony-section')) {
                        // Animer toute la section
                        animateStars();
                        animateServices();
                        animateTestimonies();
                    }
                }
            });
        }, observerOptions);
        
        const testimonySection = document.querySelector('.testimony-section');
        if (testimonySection) {
            observer.observe(testimonySection);
        }
    }
    
    // Amélioration de l'accessibilité
    function enhanceAccessibility() {
        const testimonials = document.querySelectorAll('.testimony-wrap');
        const services = document.querySelectorAll('.services-2');
        
        // Améliorer l'accessibilité des témoignages
        testimonials.forEach((testimonial, index) => {
            testimonial.setAttribute('tabindex', '0');
            testimonial.setAttribute('role', 'article');
            testimonial.setAttribute('aria-label', `Témoignage client ${index + 1}`);
            
            // Navigation au clavier
            testimonial.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    highlightTestimonial(testimonial);
                }
            });
        });
        
        // Améliorer l'accessibilité des services
        services.forEach((service, index) => {
            service.setAttribute('tabindex', '0');
            service.setAttribute('role', 'article');
            service.setAttribute('aria-label', `Service ${index + 1}`);
        });
    }
    
    // Mettre en évidence un témoignage
    function highlightTestimonial(testimonial) {
        // Retirer la classe highlight des autres témoignages
        document.querySelectorAll('.testimony-wrap').forEach(t => {
            t.classList.remove('highlighted');
        });
        
        // Ajouter la classe highlight au témoignage actuel
        testimonial.classList.add('highlighted');
        
        // Retirer la classe après 3 secondes
        setTimeout(() => {
            testimonial.classList.remove('highlighted');
        }, 3000);
    }
    
    // Effet de parallaxe subtil
    function setupParallaxEffect() {
        let ticking = false;
        
        function updateParallax() {
            const scrolled = window.pageYOffset;
            const rate = scrolled * -0.1;
            
            const testimonies = document.querySelectorAll('.testimony-wrap');
            const services = document.querySelectorAll('.services-2');
            
            testimonies.forEach((testimony, index) => {
                const offset = (index % 2 === 0) ? rate : rate * 0.5;
                testimony.style.transform = `translateY(${offset}px)`;
            });
            
            services.forEach((service, index) => {
                const offset = rate * 0.3;
                service.style.transform = `translateY(${offset}px)`;
            });
            
            ticking = false;
        }
        
        function requestTick() {
            if (!ticking) {
                requestAnimationFrame(updateParallax);
                ticking = true;
            }
        }
        
        window.addEventListener('scroll', requestTick);
    }
    
    // Gestion des badges de confiance
    function animateTrustBadges() {
        const badges = document.querySelectorAll('.trust-badge');
        
        badges.forEach((badge, index) => {
            setTimeout(() => {
                badge.style.animation = 'badgeSlideIn 0.5s ease forwards';
            }, index * 200);
        });
    }
    
    // Initialisation de toutes les fonctionnalités
    function init() {
        // Debug : vérifier la présence des éléments
        debugElements();
        
        setupScrollAnimations();
        enhanceAccessibility();
        setupParallaxEffect();
        
        // Petite attente pour que le DOM soit complètement chargé
        setTimeout(() => {
            animateTrustBadges();
            ensureVisibility(); // Double vérification
        }, 500);
    }
    
    // Fonction de débogage
    function debugElements() {
        console.log('=== DÉBUGGAGE SECTION TÉMOIGNAGE ===');
        
        const testimonySection = document.querySelector('.testimony-section');
        console.log('Section témoignage trouvée:', testimonySection ? 'OUI' : 'NON');
        
        const services = document.querySelectorAll('.services-2');
        console.log('Nombre de services trouvés:', services.length);
        
        const testimonies = document.querySelectorAll('.testimony-wrap');
        console.log('Nombre de témoignages trouvés:', testimonies.length);
        
        const carousel = document.querySelector('.carousel-testimony');
        console.log('Carousel trouvé:', carousel ? 'OUI' : 'NON');
        
        const carouselItems = document.querySelectorAll('.carousel-testimony .item');
        console.log('Nombre d\'items de carousel trouvés:', carouselItems.length);
        
        // Vérifier les styles CSS
        if (testimonies.length > 0) {
            const firstTestimony = testimonies[0];
            const styles = window.getComputedStyle(firstTestimony);
            console.log('Premier témoignage - Display:', styles.display);
            console.log('Premier témoignage - Visibility:', styles.visibility);
            console.log('Premier témoignage - Opacity:', styles.opacity);
        }
        
        console.log('=== FIN DÉBUGGAGE ===');
    }
    
    // Démarrer l'initialisation
    init();
    
    // Gestion du redimensionnement
    window.addEventListener('resize', () => {
        // Recalculer les animations si nécessaire
        const testimonies = document.querySelectorAll('.testimony-wrap');
        testimonies.forEach(testimony => {
            testimony.style.transform = 'translateY(0)';
        });
    });
});

// Ajout des styles CSS pour les nouvelles animations
const additionalStyles = `
    @keyframes star-appear {
        from {
            opacity: 0;
            transform: scale(0);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    @keyframes star-bounce {
        0%, 20%, 50%, 80%, 100% {
            transform: scale(1);
        }
        40% {
            transform: scale(1.2);
        }
        60% {
            transform: scale(1.1);
        }
    }
    
    @keyframes testimony-appear {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes service-appear {
        from {
            opacity: 0;
            transform: translateX(-30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes badgeSlideIn {
        from {
            opacity: 0;
            transform: translateX(20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .star-appear {
        animation: star-appear 0.5s ease forwards;
    }
    
    .star-bounce {
        animation: star-bounce 0.6s ease-in-out;
    }
    
    .testimony-appear {
        animation: testimony-appear 0.8s ease forwards;
    }
    
    .service-appear {
        animation: service-appear 0.6s ease forwards;
    }
    
    .testimony-wrap.highlighted {
        border: 3px solid #D4AF37;
        box-shadow: 0 0 20px rgba(212, 175, 55, 0.4);
        transform: scale(1.02);
        transition: all 0.3s ease;
    }
    
    .trust-badge {
        opacity: 0;
    }
    
    /* Amélioration du focus pour l'accessibilité */
    .testimony-wrap:focus,
    .services-2:focus {
        outline: 3px solid #D4AF37;
        outline-offset: 3px;
        border-radius: 15px;
    }
    
    /* Animation du carousel */
    .owl-carousel .owl-item {
        opacity: 0.7;
        transition: all 0.3s ease;
    }
    
    .owl-carousel .owl-item.active {
        opacity: 1;
    }
    
    /* Amélioration responsive */
    @media (max-width: 768px) {
        .testimony-wrap.highlighted {
            transform: scale(1.01);
        }
    }
`;

// Ajouter les styles supplémentaires
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);
