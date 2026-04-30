/**
 * ============================================
 * RACINE BY GANDA - Product Page JavaScript
 * Version: 2.0 (AXE E - Phase C - Refactorisé)
 * Date: 2024-01-24
 * ============================================
 * 
 * Responsabilité: Interactions page produit
 * Usage: Chargé uniquement sur routes frontend.product / product.show
 * Dépendances: Racine.Ajax, Racine.Utils (core)
 * ============================================
 */

(function () {
    'use strict';

    // Variables globales pour ce module
    let maxStock = 0;
    let productId = 0;

    /**
     * Initialisation au chargement DOM
     */
    document.addEventListener('DOMContentLoaded', function () {
        init();
    });

    /**
     * Initialisation principale
     */
    function init() {
        // Récupérer données depuis data attributes
        const qtyInput = document.getElementById('qtyInput');
        if (qtyInput) {
            maxStock = parseInt(qtyInput.getAttribute('max')) || 0;
        }

        const addToCartForm = document.getElementById('add-to-cart-form');
        if (addToCartForm) {
            const productIdInput = addToCartForm.querySelector('input[name="product_id"]');
            productId = productIdInput ? parseInt(productIdInput.value) : 0;
        }

        // Initialiser les composants
        setupQuantityControls();
        setupSizeSelection();
        setupWishlist();
        setupAddToCart();
        setupThumbnailGallery();
        setupTabs();

        console.log('[Product.js] Loaded successfully', { maxStock, productId });
    }

    /**
     * Synchroniser quantité input visible avec input hidden
     */
    function syncCartQty() {
        const input = document.getElementById('qtyInput');
        const cartInput = document.getElementById('cartQty');

        if (!input || !cartInput) return;

        let val = parseInt(input.value) || 1;

        // Limiter au stock disponible
        if (val > maxStock) {
            val = maxStock;
            input.value = val;
        }
        if (val < 1) {
            val = 1;
            input.value = val;
        }

        cartInput.value = val;
    }

    /**
     * Modifier quantité (+/-)
     */
    window.changeQty = function (delta) {
        const input = document.getElementById('qtyInput');
        if (!input) return;

        let val = parseInt(input.value) + delta;

        // Limiter entre 1 et stock disponible
        if (val < 1) val = 1;
        if (val > maxStock) val = maxStock;

        input.value = val;
        syncCartQty();
    };

    /**
     * Configurer contrôles quantité
     */
    function setupQuantityControls() {
        const input = document.getElementById('qtyInput');
        if (input) {
            input.addEventListener('change', syncCartQty);
            // Initialiser
            syncCartQty();
        }
    }

    /**
     * Sélection taille
     */
    function setupSizeSelection() {
        document.querySelectorAll('.size-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                if (this.disabled) return;
                document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });
    }

    /**
     * Wishlist toggle avec Racine.Ajax
     */
    function setupWishlist() {
        const wishlistBtn = document.getElementById('wishlistBtn');
        if (!wishlistBtn) return;

        wishlistBtn.addEventListener('click', function () {
            // Vérifier si Racine.Ajax disponible
            if (window.Racine && window.Racine.Ajax) {
                window.Racine.Ajax.toggleWishlist(productId, wishlistBtn);
            } else {
                console.warn('[Product] Racine.Ajax not loaded, using fallback');
                // Fallback : redirection login si non connecté
                const isAuthenticated = wishlistBtn.getAttribute('data-authenticated') === 'true';
                if (!isAuthenticated) {
                    const loginUrl = wishlistBtn.getAttribute('data-login-url');
                    if (loginUrl) {
                        window.location.href = loginUrl;
                    }
                }
            }
        });
    }

    /**
     * Ajout au panier avec Racine.Ajax
     */
    function setupAddToCart() {
        const addToCartForm = document.getElementById('add-to-cart-form');
        if (!addToCartForm) return;

        // Utiliser Racine.Ajax si disponible
        if (window.Racine && window.Racine.Ajax) {
            const submitBtn = document.getElementById('add-to-cart-btn');
            const submitText = document.getElementById('add-to-cart-text');

            window.Racine.Ajax.handleFormSubmit(addToCartForm, {
                submitButton: submitBtn,
                loadingText: 'Ajout...',
                onSuccess: (data) => {
                    // Succès visuel
                    if (submitBtn) {
                        const originalBg = submitBtn.style.background;
                        submitBtn.innerHTML = '<i class="fas fa-check"></i> Ajouté !';
                        submitBtn.style.background = '#22C55E';

                        // Réinitialiser après 2 secondes
                        setTimeout(() => {
                            submitBtn.innerHTML = '<i class="fas fa-shopping-bag"></i> ' + (submitText ? submitText.textContent : 'Ajouter au panier');
                            submitBtn.style.background = originalBg;
                        }, 2000);
                    }

                    // Mettre à jour compteur panier
                    if (data.count !== undefined && window.Racine.Ajax) {
                        window.Racine.Ajax.updateCartCount(data.count);
                    }
                },
                onError: (error) => {
                    // Gestion erreur stock insuffisant
                    if (error.available_stock) {
                        const qtyInput = document.getElementById('qtyInput');
                        const cartQty = document.getElementById('cartQty');
                        if (qtyInput) qtyInput.value = error.available_stock;
                        if (cartQty) cartQty.value = error.available_stock;
                    }
                }
            });
        } else {
            console.warn('[Product] Racine.Ajax not loaded, form will submit normally');
        }
    }

    /**
     * Galerie thumbnails
     */
    function setupThumbnailGallery() {
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.addEventListener('click', function () {
                document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                const mainImage = document.getElementById('mainImage');
                if (mainImage) {
                    const imgSrc = this.querySelector('img').src.replace('w=200', 'w=800');
                    mainImage.src = imgSrc;
                }
            });
        });
    }

    /**
     * Tabs produit
     */
    function setupTabs() {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                // Désactiver tous
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

                // Activer le bon
                this.classList.add('active');
                const tabId = this.dataset.tab;
                const tabContent = document.getElementById(tabId);
                if (tabContent) {
                    tabContent.classList.add('active');
                }
            });
        });
    }

})();
