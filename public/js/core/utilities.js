/**
 * RACINE BY GANDA — Core Utilities v1.1
 * Namespace: Racine.Utils
 */

window.Racine = window.Racine || {};

window.Racine.Utils = window.Racine.Utils || (function () {
    'use strict';

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function showNotification(message, type) {
        document.dispatchEvent(new CustomEvent('racine:toast', {
            detail: { message: message, type: type || 'info' }
        }));
    }

    function showButtonLoader(btn, text) {
        if (window.Racine && window.Racine.Loading) {
            window.Racine.Loading.showButtonLoader(btn, text);
        } else {
            if (btn) {
                btn.disabled   = true;
                btn._origText  = btn.innerHTML;
                btn.innerHTML  = text || 'Chargement…';
            }
        }
    }

    function hideButtonLoader(btn) {
        if (window.Racine && window.Racine.Loading) {
            window.Racine.Loading.hideButtonLoader(btn);
        } else {
            if (btn && btn._origText !== undefined) {
                btn.disabled  = false;
                btn.innerHTML = btn._origText;
                delete btn._origText;
            }
        }
    }

    function formatFCFA(amount) {
        return parseInt(amount).toLocaleString('fr-FR') + ' FCFA';
    }

    function debounce(fn, delay) {
        var timer;
        return function () {
            var args = arguments, ctx = this;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(ctx, args); }, delay);
        };
    }

    return { csrfToken, showNotification, showButtonLoader, hideButtonLoader, formatFCFA, debounce };
})();
