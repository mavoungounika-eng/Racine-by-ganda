/**
 * ============================================
 * RACINE BY GANDA - Core AJAX Utilities
 * Version: 1.0
 * Date: 2024-01-24
 * Phase: AXE E - Cleanup JavaScript
 * ============================================
 * 
 * Responsabilité: Centraliser patterns AJAX récurrents
 * Usage: Chargé sur pages avec AJAX (cart, checkout, wishlist)
 * Namespace: Racine.Ajax
 * ============================================
 */

window.Racine = window.Racine || {};

/**
 * Module AJAX
 */
window.Racine.Ajax = (function () {
    'use strict';

    /**
     * Handler AJAX générique pour formulaires
     * @param {HTMLFormElement} form - Formulaire à soumettre
     * @param {Object} options - Options de configuration
     * @param {Function} options.onSuccess - Callback succès (data)
     * @param {Function} options.onError - Callback erreur (error)
     * @param {Function} options.onFinally - Callback final (toujours appelé)
     * @param {HTMLElement} options.submitButton - Bouton de soumission (pour loader)
     * @param {string} options.loadingText - Texte pendant chargement
     */
    function handleFormSubmit(form, options = {}) {
        if (!form) {
            console.error('[Racine.Ajax] Form element is required');
            return;
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitButton = options.submitButton || this.querySelector('button[type="submit"]');
            const loadingText = options.loadingText || 'Envoi...';

            // Afficher loader sur bouton
            if (submitButton && window.Racine.Utils) {
                window.Racine.Utils.showButtonLoader(submitButton, loadingText);
            }

            // Requête AJAX
            fetch(this.action, {
                method: this.method || 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': window.Racine.Utils?.csrfToken() || ''
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        if (options.onSuccess) {
                            options.onSuccess(data);
                        }
                    } else {
                        if (options.onError) {
                            options.onError(data);
                        } else if (window.Racine.Utils) {
                            window.Racine.Utils.showNotification(
                                data.message || 'Une erreur est survenue',
                                'error'
                            );
                        }
                    }
                })
                .catch(error => {
                    console.error('[Racine.Ajax] Error:', error);
                    if (options.onError) {
                        options.onError(error);
                    } else if (window.Racine.Utils) {
                        window.Racine.Utils.showNotification(
                            'Erreur réseau. Veuillez réessayer.',
                            'error'
                        );
                    }
                })
                .finally(() => {
                    // Masquer loader
                    if (submitButton && window.Racine.Utils) {
                        window.Racine.Utils.hideButtonLoader(submitButton);
                    }

                    if (options.onFinally) {
                        options.onFinally();
                    }
                });
        });
    }

    /**
     * Mettre à jour le compteur du panier
     * @param {number} count - Nouveau nombre d'articles
     */
    function updateCartCount(count) {
        // Badge principal
        const cartBadge = document.getElementById('cart-count-badge');
        if (cartBadge) {
            cartBadge.textContent = count;
            cartBadge.style.display = count > 0 ? 'flex' : 'none';

            // Animation
            cartBadge.style.transform = 'scale(1.2)';
            cartBadge.style.transition = 'transform 0.3s';
            setTimeout(() => {
                cartBadge.style.transform = 'scale(1)';
            }, 300);
        }

        // Autres sélecteurs possibles
        document.querySelectorAll('#cart-count, .cart-count').forEach(el => {
            if (el) {
                el.textContent = count;
            }
        });
    }

    /**
     * Requête JSON générique
     * @param {string} url - URL de la requête
     * @param {Object} options - Options fetch
     * @returns {Promise} Promise avec réponse JSON
     */
    function fetchJSON(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': window.Racine.Utils?.csrfToken() || '',
                ...options.headers
            }
        };

        return fetch(url, { ...defaultOptions, ...options })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            });
    }

    /**
     * Ajouter un produit au panier (AJAX)
     * @param {number} productId - ID du produit
     * @param {number} quantity - Quantité
     * @param {Object} options - Options additionnelles
     * @returns {Promise} Promise de l'ajout
     */
    function addToCart(productId, quantity = 1, options = {}) {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', quantity);

        if (options.variant_id) {
            formData.append('variant_id', options.variant_id);
        }

        return fetch('/cart/add', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': window.Racine.Utils?.csrfToken() || ''
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour compteur
                    if (data.count !== undefined) {
                        updateCartCount(data.count);
                    }

                    // Notification
                    if (window.Racine.Utils) {
                        window.Racine.Utils.showNotification(
                            data.message || 'Produit ajouté au panier !',
                            'success'
                        );
                    }
                }
                return data;
            });
    }

    /**
     * Toggle wishlist (AJAX)
     * @param {number} productId - ID du produit
     * @param {HTMLElement} button - Bouton wishlist (pour icône)
     * @returns {Promise} Promise du toggle
     */
    function toggleWishlist(productId, button = null) {
        const formData = new FormData();
        formData.append('product_id', productId);

        return fetch('/profile/wishlist/toggle', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': window.Racine.Utils?.csrfToken() || ''
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && button) {
                    const icon = button.querySelector('i');
                    if (icon) {
                        if (data.is_in_wishlist) {
                            icon.classList.remove('far');
                            icon.classList.add('fas');
                            button.style.color = '#DC2626';
                        } else {
                            icon.classList.remove('fas');
                            icon.classList.add('far');
                            button.style.color = '';
                        }
                    }

                    // Notification
                    if (window.Racine.Utils) {
                        window.Racine.Utils.showNotification(data.message, 'success');
                    }
                }
                return data;
            });
    }

    // API publique
    return {
        handleFormSubmit,
        updateCartCount,
        fetchJSON,
        addToCart,
        toggleWishlist
    };
})();
