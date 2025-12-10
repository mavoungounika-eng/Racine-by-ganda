/**
 * RACINE AJAX Spinner - Logo R animé
 * Version standalone sans dépendances
 */

(function() {
    'use strict';
    
    // Créer le container du spinner
    const createSpinnerHTML = () => {
        return `
            <div class="racine-logo-anim-container" data-variant="spinner" data-theme="dark" style="width: 60px; height: 72px; position: relative; display: flex; align-items: center; justify-content: center;">
                <svg class="racine-logo-svg" viewBox="0 0 200 240" xmlns="http://www.w3.org/2000/svg" style="width: 60px; height: 72px;">
                    <defs>
                        <filter id="racine-glow-spinner">
                            <feGaussianBlur stdDeviation="4" result="coloredBlur"/>
                            <feMerge>
                                <feMergeNode in="coloredBlur"/>
                                <feMergeNode in="SourceGraphic"/>
                            </feMerge>
                        </filter>
                    </defs>
                    <path class="racine-segment" 
                          d="M 40 30 L 40 180" 
                          stroke="#ED5F1E" 
                          stroke-width="18" 
                          stroke-linecap="round"
                          fill="none"
                          filter="url(#racine-glow-spinner)"
                          stroke-dasharray="150"
                          stroke-dashoffset="150"/>
                </svg>
                <style>
                    .racine-logo-anim-container.active .racine-segment {
                        animation: drawR 1s ease-out forwards, spinR 2s ease-in-out infinite 1s;
                    }
                    @keyframes drawR {
                        to { stroke-dashoffset: 0; }
                    }
                    @keyframes spinR {
                        0%, 100% { transform: rotate(0deg); opacity: 1; }
                        50% { transform: rotate(180deg); opacity: 0.7; }
                    }
                </style>
            </div>
        `;
    };
    
    window.RacineAjaxSpinner = {
        container: null,
        
        init: function() {
            if (!document.getElementById('racine-ajax-spinner')) {
                const spinnerDiv = document.createElement('div');
                spinnerDiv.id = 'racine-ajax-spinner';
                spinnerDiv.className = 'racine-ajax-spinner-container';
                spinnerDiv.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(22, 13, 12, 0.8); backdrop-filter: blur(2px); display: none; align-items: center; justify-content: center; z-index: 99998;';
                document.body.appendChild(spinnerDiv);
                this.container = spinnerDiv;
            } else {
                this.container = document.getElementById('racine-ajax-spinner');
            }
            
            this.interceptAjax();
        },
        
        interceptAjax: function() {
            const self = this;
            
            // jQuery AJAX
            if (typeof window.$ !== 'undefined' && window.$.ajax) {
                $(document).ajaxStart(function() {
                    self.show();
                }).ajaxStop(function() {
                    self.hide();
                });
            }
        },
        
        show: function() {
            if (this.container) {
                this.container.innerHTML = createSpinnerHTML();
                this.container.style.display = 'flex';
                const animContainer = this.container.querySelector('.racine-logo-anim-container');
                if (animContainer) {
                    setTimeout(() => {
                        animContainer.classList.add('active');
                    }, 50);
                }
            }
        },
        
        hide: function() {
            if (this.container) {
                setTimeout(() => {
                    if (this.container) {
                        this.container.style.display = 'none';
                        this.container.innerHTML = '';
                    }
                }, 300);
            }
        }
    };
    
    // Auto-initialiser
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            RacineAjaxSpinner.init();
        });
    } else {
        RacineAjaxSpinner.init();
    }
})();


