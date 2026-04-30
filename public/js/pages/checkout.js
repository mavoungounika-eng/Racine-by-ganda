/**
 * ============================================
 * RACINE BY GANDA - Checkout Page JavaScript
 * Version: 1.0 (AXE E - Phase C)
 * Date: 2024-01-24
 * ============================================
 * 
 * Responsabilité: Interactions page checkout
 * Usage: Chargé uniquement sur route checkout.index
 * ============================================
 */

(function () {
    'use strict';

    /**
     * Mise à jour dynamique du coût de livraison et du total
     */
    document.addEventListener('DOMContentLoaded', function () {
        const shippingInputs = document.querySelectorAll('input[name="shipping_method"]');
        const shippingDisplay = document.getElementById('shipping-cost-display');
        const totalDisplay = document.getElementById('total-display');

        if (!shippingInputs.length || !shippingDisplay || !totalDisplay) {
            console.warn('[Checkout] Required elements not found');
            return;
        }

        // Récupérer subtotal depuis data attribute
        const subtotal = parseInt(totalDisplay.getAttribute('data-subtotal')) || 0;

        if (!subtotal) {
            console.error('[Checkout] Subtotal not found in data-subtotal attribute');
            return;
        }

        shippingInputs.forEach(input => {
            input.addEventListener('change', function () {
                // Coût livraison : 2000 FCFA pour home_delivery, 0 pour showroom_pickup
                const shipping = this.value === 'home_delivery' ? 2000 : 0;
                const total = subtotal + shipping;

                // Mettre à jour affichage
                shippingDisplay.textContent = shipping.toLocaleString('fr-FR') + ' FCFA';
                totalDisplay.textContent = total.toLocaleString('fr-FR') + ' FCFA';
            });
        });
    });

    // Log confirmation chargement
    console.log('[Checkout.js] Loaded successfully');
})();
