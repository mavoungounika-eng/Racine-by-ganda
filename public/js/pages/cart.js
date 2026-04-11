/**
 * ============================================
 * RACINE BY GANDA - Cart Page JavaScript
 * Version: 1.0 (AXE E - Phase C)
 * Date: 2024-01-24
 * ============================================
 * 
 * Responsabilité: Interactions page panier
 * Usage: Chargé uniquement sur route cart.index
 * ============================================
 */

(function () {
    'use strict';

    /**
     * Mettre à jour la quantité d'un article dans le panier
     * @param {HTMLElement} btn - Bouton +/- cliqué
     * @param {number} delta - +1 ou -1
     */
    window.updateQty = function (btn, delta) {
        const form = btn.closest('form');
        if (!form) {
            console.error('[Cart] Form not found');
            return;
        }

        const input = form.querySelector('.qty-input');
        if (!input) {
            console.error('[Cart] Quantity input not found');
            return;
        }

        let val = parseInt(input.value) + delta;

        // Limiter entre 1 et 99
        if (val < 1) val = 1;
        if (val > 99) val = 99;

        input.value = val;

        // Soumettre le formulaire pour mise à jour backend
        form.submit();
    };

    // Log confirmation chargement
    console.log('[Cart.js] Loaded successfully');
})();
