/**
 * ============================================
 * RACINE BY GANDA - Core Utilities
 * Version: 1.0
 * Date: 2024-01-24
 * Phase: AXE E - Cleanup JavaScript
 * ============================================
 * 
 * Responsabilité: Fonctions utilitaires globales
 * Usage: Chargé sur TOUTES les pages (base)
 * Namespace: Racine.Utils
 * ============================================
 */

// Namespace global Racine
window.Racine = window.Racine || {};

/**
 * Module Utilities
 */
window.Racine.Utils = (function () {
    'use strict';

    /**
     * Afficher une notification toast
     * @param {string} message - Message à afficher
     * @param {string} type - Type de notification (success, error, warning, info)
     */
    function showNotification(message, type = 'success') {
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'danger': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        }[type] || 'alert-info';

        const iconClass = {
            'success': 'fa-check-circle',
            'error': 'fa-exclamation-circle',
            'danger': 'fa-exclamation-circle',
            'warning': 'fa-exclamation-triangle',
            'info': 'fa-info-circle'
        }[type] || 'fa-info-circle';

        // Créer l'élément alert
        const alert = document.createElement('div');
        alert.className = `alert ${alertClass} alert-dismissible fade show`;
        alert.setAttribute('role', 'alert');
        alert.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 500px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            animation: slideInRight 0.3s ease-out;
        `;

        alert.innerHTML = `
            <i class="fas ${iconClass} me-2"></i>
            <strong>${message}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        // Ajouter au DOM
        document.body.appendChild(alert);

        // Auto-dismiss après 5 secondes
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    }

    /**
     * Formater un montant en FCFA
     * @param {number} amount - Montant à formater
     * @returns {string} Montant formaté
     */
    function formatAmount(amount) {
        return new Intl.NumberFormat('fr-FR').format(amount) + ' FCFA';
    }

    /**
     * Valider un numéro de téléphone (10 chiffres)
     * @param {string} phone - Numéro à valider
     * @returns {boolean} True si valide
     */
    function validatePhone(phone) {
        const phoneRegex = /^[0-9]{10}$/;
        return phoneRegex.test(phone);
    }

    /**
     * Obtenir le token CSRF
     * @returns {string|null} Token CSRF ou null
     */
    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : null;
    }

    /**
     * Afficher un loader sur un bouton
     * @param {HTMLElement} button - Bouton cible
     * @param {string} text - Texte pendant le chargement
     */
    function showButtonLoader(button, text = 'Chargement...') {
        if (!button) return;

        const originalContent = button.innerHTML;
        button.setAttribute('data-original-content', originalContent);
        button.disabled = true;
        button.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i>${text}`;
    }

    /**
     * Masquer le loader d'un bouton
     * @param {HTMLElement} button - Bouton cible
     */
    function hideButtonLoader(button) {
        if (!button) return;

        const originalContent = button.getAttribute('data-original-content');
        if (originalContent) {
            button.disabled = false;
            button.innerHTML = originalContent;
            button.removeAttribute('data-original-content');
        }
    }

    /**
     * Copier du texte dans le presse-papiers
     * @param {string} text - Texte à copier
     * @returns {Promise} Promise de copie
     */
    function copyToClipboard(text) {
        return navigator.clipboard.writeText(text);
    }

    /**
     * Formater une date
     * @param {Date|string} date - Date à formater
     * @param {string} format - Format souhaité (short, long, full)
     * @returns {string} Date formatée
     */
    function formatDate(date, format = 'short') {
        const d = new Date(date);
        const options = {
            'short': { day: '2-digit', month: '2-digit', year: 'numeric' },
            'long': { day: 'numeric', month: 'long', year: 'numeric' },
            'full': { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }
        };

        return new Intl.DateTimeFormat('fr-FR', options[format] || options.short).format(d);
    }

    /**
     * Debounce une fonction
     * @param {Function} func - Fonction à debounce
     * @param {number} wait - Délai en ms
     * @returns {Function} Fonction debouncée
     */
    function debounce(func, wait = 300) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // API publique
    return {
        showNotification,
        formatAmount,
        validatePhone,
        csrfToken,
        showButtonLoader,
        hideButtonLoader,
        copyToClipboard,
        formatDate,
        debounce
    };
})();

// Alias global pour rétrocompatibilité (DEPRECATED - utiliser Racine.Utils.showNotification)
window.showNotification = window.Racine.Utils.showNotification;

// Animation CSS pour slideInRight
if (!document.getElementById('racine-utils-styles')) {
    const style = document.createElement('style');
    style.id = 'racine-utils-styles';
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(style);
}
