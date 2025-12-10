/**
 * RACINE AJAX Spinner - Logo R animé
 * Affiche un spinner avec le logo R lors des requêtes AJAX
 */

window.RacineAjaxSpinner = {
    container: null,
    
    init: function() {
        // Créer le container si inexistant
        if (!document.getElementById('racine-ajax-spinner')) {
            const spinnerHTML = `
                <div id="racine-ajax-spinner" class="racine-ajax-spinner-container" style="display: none;">
                    @include('components.racine-logo-animation', ['variant' => 'spinner', 'theme' => 'dark'])
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', spinnerHTML);
            this.container = document.getElementById('racine-ajax-spinner');
        } else {
            this.container = document.getElementById('racine-ajax-spinner');
        }
        
        // Intercepter les requêtes AJAX
        this.interceptAjax();
    },
    
    interceptAjax: function() {
        const self = this;
        
        // jQuery AJAX
        if (typeof $ !== 'undefined') {
            $(document).ajaxStart(function() {
                self.show();
            }).ajaxStop(function() {
                self.hide();
            });
        }
        
        // Fetch API
        const originalFetch = window.fetch;
        let activeRequests = 0;
        
        window.fetch = function(...args) {
            activeRequests++;
            self.show();
            
            return originalFetch.apply(this, args).finally(() => {
                activeRequests--;
                if (activeRequests === 0) {
                    self.hide();
                }
            });
        };
        
        // XMLHttpRequest
        const originalOpen = XMLHttpRequest.prototype.open;
        const originalSend = XMLHttpRequest.prototype.send;
        let xhrRequests = 0;
        
        XMLHttpRequest.prototype.open = function(...args) {
            this.addEventListener('loadstart', () => {
                xhrRequests++;
                self.show();
            });
            
            this.addEventListener('loadend', () => {
                xhrRequests--;
                if (xhrRequests === 0) {
                    setTimeout(() => self.hide(), 300);
                }
            });
            
            return originalOpen.apply(this, args);
        };
    },
    
    show: function() {
        if (this.container) {
            this.container.style.display = 'flex';
            const animContainer = this.container.querySelector('.racine-logo-anim-container');
            if (animContainer) {
                animContainer.classList.add('active');
                animContainer.classList.add('animated');
            }
        }
    },
    
    hide: function() {
        if (this.container) {
            const animContainer = this.container.querySelector('.racine-logo-anim-container');
            if (animContainer) {
                animContainer.classList.remove('active');
                animContainer.classList.remove('animated');
            }
            setTimeout(() => {
                if (this.container) {
                    this.container.style.display = 'none';
                }
            }, 300);
        }
    }
};

// Auto-initialiser au chargement
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        RacineAjaxSpinner.init();
    });
} else {
    RacineAjaxSpinner.init();
}

// Exporter pour utilisation globale
window.RacineAjaxSpinner = RacineAjaxSpinner;


