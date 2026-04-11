/**
 * SCRIPT DE DÉBOGAGE ET CORRECTION ULTIME - SECTION TÉMOIGNAGE
 * RACINE BY GANDA
 */

(function() {
    'use strict';
    
    // Configuration de débogage
    const DEBUG = true;
    const FORCE_DISPLAY = true;
    
    function log(message, data = null) {
        if (DEBUG) {
            console.log(`[TESTIMONY DEBUG] ${message}`, data || '');
        }
    }
    
    function error(message, data = null) {
        console.error(`[TESTIMONY ERROR] ${message}`, data || '');
    }
    
    // Fonction pour forcer l'affichage d'un élément
    function forceDisplay(element, displayType = 'block') {
        if (!element) return;
        
        element.style.display = displayType;
        element.style.visibility = 'visible';
        element.style.opacity = '1';
        element.style.position = 'relative';
        
        // Supprimer les classes qui pourraient masquer l'élément
        element.classList.remove('hidden', 'hide', 'invisible');
        
        // Supprimer les attributs qui pourraient masquer l'élément
        element.removeAttribute('hidden');
        element.removeAttribute('style');
        
        // Appliquer les styles forcés
        element.style.display = displayType + ' !important';
        element.style.visibility = 'visible !important';
        element.style.opacity = '1 !important';
    }
    
    // Fonction pour diagnostiquer les problèmes
    function diagnoseSection() {
        log('=== DIAGNOSTIC DE LA SECTION TÉMOIGNAGE ===');
        
        const section = document.querySelector('.testimony-section');
        if (!section) {
            error('Section .testimony-section non trouvée !');
            return;
        }
        
        log('Section trouvée', {
            display: getComputedStyle(section).display,
            visibility: getComputedStyle(section).visibility,
            opacity: getComputedStyle(section).opacity,
            height: section.offsetHeight,
            width: section.offsetWidth
        });
        
        // Vérifier les services
        const services = section.querySelectorAll('.services-2');
        log(`${services.length} services trouvés`);
        
        services.forEach((service, index) => {
            const styles = getComputedStyle(service);
            log(`Service ${index + 1}:`, {
                display: styles.display,
                visibility: styles.visibility,
                opacity: styles.opacity,
                height: service.offsetHeight
            });
        });
        
        // Vérifier les témoignages
        const testimonials = section.querySelectorAll('.testimony-wrap');
        log(`${testimonials.length} témoignages trouvés`);
        
        testimonials.forEach((testimony, index) => {
            const styles = getComputedStyle(testimony);
            log(`Témoignage ${index + 1}:`, {
                display: styles.display,
                visibility: styles.visibility,
                opacity: styles.opacity,
                height: testimony.offsetHeight
            });
        });
        
        // Vérifier le carousel
        const carousel = section.querySelector('.carousel-testimony');
        if (carousel) {
            const carouselStyles = getComputedStyle(carousel);
            log('Carousel:', {
                display: carouselStyles.display,
                visibility: carouselStyles.visibility,
                opacity: carouselStyles.opacity,
                height: carousel.offsetHeight
            });
        }
    }
    
    // Fonction pour forcer l'affichage de toute la section
    function forceDisplaySection() {
        log('=== FORÇAGE DE L\'AFFICHAGE ===');
        
        const section = document.querySelector('.testimony-section');
        if (!section) {
            error('Section .testimony-section non trouvée pour le forçage !');
            return;
        }
        
        // Forcer l'affichage de la section principale
        forceDisplay(section, 'block');
        section.style.minHeight = '400px';
        section.style.padding = '80px 0';
        section.style.background = 'linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%)';
        
        // Forcer l'affichage du container
        const container = section.querySelector('.container');
        if (container) {
            forceDisplay(container, 'block');
        }
        
        // Forcer l'affichage des colonnes
        const columns = section.querySelectorAll('.col-lg-5, .col-lg-7');
        columns.forEach(col => {
            forceDisplay(col, 'block');
            col.style.float = 'left';
            col.style.width = col.classList.contains('col-lg-5') ? '41.66%' : '58.33%';
        });
        
        // Forcer l'affichage des services
        const servicesFlow = section.querySelector('.services-flow');
        if (servicesFlow) {
            forceDisplay(servicesFlow, 'block');
            servicesFlow.style.background = 'rgba(255, 255, 255, 0.95)';
            servicesFlow.style.borderRadius = '20px';
            servicesFlow.style.padding = '30px';
            servicesFlow.style.boxShadow = '0 15px 35px rgba(0, 0, 0, 0.1)';
        }
        
        const services = section.querySelectorAll('.services-2');
        services.forEach(service => {
            forceDisplay(service, 'flex');
            service.style.alignItems = 'center';
            service.style.background = 'linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%)';
            service.style.borderRadius = '15px';
            service.style.marginBottom = '20px';
            service.style.padding = '20px';
            service.style.border = '1px solid rgba(0, 0, 0, 0.05)';
            
            // Forcer l'affichage des éléments internes
            const icon = service.querySelector('.icon');
            const text = service.querySelector('.text');
            if (icon) {
                forceDisplay(icon, 'block');
                icon.style.marginRight = '20px';
                icon.style.fontSize = '2rem';
                icon.style.color = '#D4AF37';
            }
            if (text) {
                forceDisplay(text, 'block');
                text.style.flex = '1';
            }
        });
        
        // Forcer l'affichage de la section témoignages
        const headingSection = section.querySelector('.heading-section');
        if (headingSection) {
            forceDisplay(headingSection, 'block');
            
            const h2 = headingSection.querySelector('h2');
            const p = headingSection.querySelector('p');
            if (h2) {
                forceDisplay(h2, 'block');
                h2.style.color = '#2c3e50';
                h2.style.fontWeight = '700';
                h2.style.fontSize = '2.5rem';
                h2.style.marginBottom = '20px';
            }
            if (p) {
                forceDisplay(p, 'block');
                p.style.color = '#6c757d';
                p.style.fontSize = '1.1rem';
                p.style.lineHeight = '1.6';
            }
        }
        
        // Forcer l'affichage du carousel
        const carousel = section.querySelector('.carousel-testimony');
        if (carousel) {
            forceDisplay(carousel, 'block');
            carousel.style.padding = '20px 0';
            carousel.style.minHeight = '300px';
            
            // Forcer l'affichage des items
            const items = carousel.querySelectorAll('.item');
            items.forEach(item => {
                forceDisplay(item, 'block');
                item.style.marginBottom = '30px';
                
                // Forcer l'affichage des témoignages
                const testimony = item.querySelector('.testimony-wrap');
                if (testimony) {
                    forceDisplay(testimony, 'block');
                    testimony.style.background = 'linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%)';
                    testimony.style.borderRadius = '20px';
                    testimony.style.padding = '40px 30px';
                    testimony.style.margin = '10px';
                    testimony.style.boxShadow = '0 15px 35px rgba(0, 0, 0, 0.08)';
                    testimony.style.border = '1px solid rgba(0, 0, 0, 0.05)';
                    testimony.style.position = 'relative';
                    testimony.style.minHeight = '250px';
                    
                    // Forcer l'affichage du badge
                    const badge = testimony.querySelector('.trust-badge');
                    if (badge) {
                        forceDisplay(badge, 'inline-block');
                        badge.style.position = 'absolute';
                        badge.style.top = '-15px';
                        badge.style.right = '20px';
                        badge.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
                        badge.style.color = 'white';
                        badge.style.padding = '8px 16px';
                        badge.style.borderRadius = '20px';
                        badge.style.fontSize = '0.85rem';
                        badge.style.fontWeight = '600';
                        badge.style.boxShadow = '0 4px 12px rgba(40, 167, 69, 0.3)';
                        badge.style.zIndex = '10';
                    }
                    
                    // Forcer l'affichage de l'image utilisateur
                    const userImg = testimony.querySelector('.user-img');
                    if (userImg) {
                        forceDisplay(userImg, 'block');
                        userImg.style.width = '80px';
                        userImg.style.height = '80px';
                        userImg.style.borderRadius = '50%';
                        userImg.style.margin = '0 auto 20px auto';
                        userImg.style.border = '4px solid #fff';
                        userImg.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.1)';
                        userImg.style.overflow = 'hidden';
                        userImg.style.backgroundSize = 'cover';
                        userImg.style.backgroundPosition = 'center';
                        
                        const quote = userImg.querySelector('.quote');
                        if (quote) {
                            forceDisplay(quote, 'flex');
                            quote.style.position = 'absolute';
                            quote.style.bottom = '-10px';
                            quote.style.right = '-10px';
                            quote.style.width = '35px';
                            quote.style.height = '35px';
                            quote.style.background = 'linear-gradient(135deg, #D4AF37 0%, #B8860B 100%)';
                            quote.style.borderRadius = '50%';
                            quote.style.border = '3px solid #fff';
                            quote.style.boxShadow = '0 3px 10px rgba(0, 0, 0, 0.2)';
                            quote.style.alignItems = 'center';
                            quote.style.justifyContent = 'center';
                            
                            const icon = quote.querySelector('i');
                            if (icon) {
                                icon.style.color = 'white';
                                icon.style.fontSize = '14px';
                            }
                        }
                    }
                    
                    // Forcer l'affichage du texte
                    const text = testimony.querySelector('.text');
                    if (text) {
                        forceDisplay(text, 'block');
                        text.style.textAlign = 'center';
                        text.style.paddingTop = '20px';
                        
                        // Forcer l'affichage des étoiles
                        const stars = text.querySelector('.star-rating');
                        if (stars) {
                            forceDisplay(stars, 'flex');
                            stars.style.justifyContent = 'center';
                            stars.style.marginBottom = '20px';
                            
                            const starIcons = stars.querySelectorAll('i');
                            starIcons.forEach(star => {
                                forceDisplay(star, 'inline-block');
                                star.style.color = '#FFD700';
                                star.style.fontSize = '1.2rem';
                                star.style.margin = '0 2px';
                            });
                        }
                        
                        // Forcer l'affichage des paragraphes
                        const paragraphs = text.querySelectorAll('p');
                        paragraphs.forEach(p => {
                            forceDisplay(p, 'block');
                            if (p.classList.contains('line')) {
                                p.style.fontStyle = 'italic';
                                p.style.color = '#495057';
                                p.style.fontSize = '1.1rem';
                                p.style.lineHeight = '1.7';
                                p.style.position = 'relative';
                                p.style.padding = '0 20px';
                                p.style.marginBottom = '25px';
                            } else if (p.classList.contains('name')) {
                                p.style.fontWeight = '600';
                                p.style.color = '#2c3e50';
                                p.style.fontSize = '1.1rem';
                                p.style.marginBottom = '5px';
                            }
                        });
                        
                        // Forcer l'affichage du span position
                        const position = text.querySelector('span.position');
                        if (position) {
                            forceDisplay(position, 'inline-block');
                            position.style.color = '#6c757d';
                            position.style.fontSize = '0.95rem';
                            position.style.fontStyle = 'italic';
                        }
                    }
                }
            });
        }
        
        // Ajouter une classe pour indiquer que la section est chargée
        section.classList.add('loaded');
        
        log('Forçage terminé');
    }
    
    // Fonction pour désactiver Owl Carousel s'il cause des problèmes
    function disableOwlCarousel() {
        log('Désactivation d\'Owl Carousel si présent');
        
        const carousel = document.querySelector('.carousel-testimony');
        if (carousel && carousel.classList.contains('owl-carousel')) {
            // Retirer les classes Owl Carousel
            carousel.classList.remove('owl-carousel');
            carousel.classList.remove('owl-theme');
            
            // Supprimer les styles Owl Carousel
            carousel.style.display = 'block';
            carousel.style.visibility = 'visible';
            carousel.style.opacity = '1';
            
            // Forcer l'affichage des items
            const items = carousel.querySelectorAll('.item');
            items.forEach(item => {
                item.style.display = 'block';
                item.style.visibility = 'visible';
                item.style.opacity = '1';
                item.style.position = 'relative';
                item.style.float = 'none';
                item.style.width = 'auto';
                item.style.marginBottom = '30px';
            });
            
            log('Owl Carousel désactivé');
        }
    }
    
    // Fonction principale d'initialisation
    function init() {
        log('=== INITIALISATION DU CORRECTIF TÉMOIGNAGE ===');
        
        // Diagnostic initial
        diagnoseSection();
        
        // Désactiver Owl Carousel si nécessaire
        disableOwlCarousel();
        
        // Forcer l'affichage
        if (FORCE_DISPLAY) {
            forceDisplaySection();
        }
        
        // Nouveau diagnostic après correction
        setTimeout(() => {
            log('=== DIAGNOSTIC APRÈS CORRECTION ===');
            diagnoseSection();
        }, 1000);
        
        log('Initialisation terminée');
    }
    
    // Lancer l'initialisation quand le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Lancer aussi après le chargement complet
    if (document.readyState !== 'complete') {
        window.addEventListener('load', () => {
            setTimeout(init, 500);
        });
    }
    
    // Exposer les fonctions de débogage globalement
    window.TestimonyDebug = {
        diagnose: diagnoseSection,
        forceDisplay: forceDisplaySection,
        disableOwl: disableOwlCarousel,
        init: init
    };
    
    log('Script de débogage chargé. Utilisez window.TestimonyDebug pour accéder aux fonctions.');
    
})();
