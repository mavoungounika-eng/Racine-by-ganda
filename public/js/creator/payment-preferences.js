/**
 * PAYMENT PREFERENCES - JAVASCRIPT
 * Gestion des interactions et validations
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // VALIDATION DU FORMULAIRE MOBILE MONEY
    // ============================================
    
    const mobileMoneyForm = document.getElementById('mobileMoneyForm');
    
    if (mobileMoneyForm) {
        const operatorSelect = document.getElementById('operator');
        const phoneInput = document.getElementById('phone');
        
        // Validation en temps réel du numéro de téléphone
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                // Ne garder que les chiffres
                this.value = this.value.replace(/\D/g, '');
                
                // Limiter à 10 chiffres
                if (this.value.length > 10) {
                    this.value = this.value.slice(0, 10);
                }
                
                // Validation visuelle
                if (this.value.length === 10) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else if (this.value.length > 0) {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                } else {
                    this.classList.remove('is-invalid', 'is-valid');
                }
            });
        }
        
        // Validation avant soumission
        mobileMoneyForm.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Vérifier l'opérateur
            if (!operatorSelect.value) {
                operatorSelect.classList.add('is-invalid');
                isValid = false;
            } else {
                operatorSelect.classList.remove('is-invalid');
            }
            
            // Vérifier le numéro
            if (!phoneInput.value || phoneInput.value.length !== 10) {
                phoneInput.classList.add('is-invalid');
                isValid = false;
            } else {
                phoneInput.classList.remove('is-invalid');
            }
            
            if (!isValid) {
                e.preventDefault();
                showNotification('Veuillez remplir tous les champs correctement', 'error');
            }
        });
    }
    
    // ============================================
    // AUTO-DISMISS DES ALERTES
    // ============================================
    
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
    // ============================================
    // TOOLTIPS BOOTSTRAP
    // ============================================
    
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    if (typeof bootstrap !== 'undefined') {
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // ============================================
    // ANIMATION DES CARTES AU SCROLL
    // ============================================
    
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    const cards = document.querySelectorAll('.payment-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = `opacity 0.5s ease ${index * 0.1}s, transform 0.5s ease ${index * 0.1}s`;
        observer.observe(card);
    });
    
    // ============================================
    // HELPER: AFFICHER UNE NOTIFICATION
    // ============================================
    
    function showNotification(message, type = 'info') {
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        }[type] || 'alert-info';
        
        const iconClass = {
            'success': 'fa-check-circle',
            'error': 'fa-exclamation-circle',
            'warning': 'fa-exclamation-triangle',
            'info': 'fa-info-circle'
        }[type] || 'fa-info-circle';
        
        const alert = document.createElement('div');
        alert.className = `alert ${alertClass} alert-dismissible`;
        alert.innerHTML = `
            <i class="fas ${iconClass}"></i>
            ${message}
            <button type="button" class="alert-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        const container = document.querySelector('.payment-preferences-container');
        if (container) {
            container.insertBefore(alert, container.firstChild);
            
            // Auto-dismiss après 5 secondes
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        }
    }
    
    // ============================================
    // CONFIRMATION DE DÉCONNEXION STRIPE
    // ============================================
    
    const disconnectForms = document.querySelectorAll('form[action*="disconnect"]');
    disconnectForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const confirmed = confirm(
                'Êtes-vous sûr de vouloir déconnecter Stripe?\n\n' +
                'Cette action désactivera tous vos paiements et vous ne pourrez plus recevoir de revenus.\n\n' +
                'Vous pourrez reconnecter votre compte à tout moment.'
            );
            
            if (!confirmed) {
                e.preventDefault();
            }
        });
    });
    
    // ============================================
    // SMOOTH SCROLL POUR LES LIENS INTERNES
    // ============================================
    
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href !== '#!') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
    
    // ============================================
    // COPIER LE NUMÉRO DE COMPTE (si présent)
    // ============================================
    
    const copyButtons = document.querySelectorAll('[data-copy]');
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const textToCopy = this.getAttribute('data-copy');
            
            navigator.clipboard.writeText(textToCopy).then(() => {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i> Copié!';
                this.classList.add('btn-payment--success');
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.classList.remove('btn-payment--success');
                }, 2000);
            }).catch(err => {
                console.error('Erreur lors de la copie:', err);
                showNotification('Impossible de copier le texte', 'error');
            });
        });
    });
    
});

/**
 * Formater un montant en FCFA
 */
function formatAmount(amount) {
    return new Intl.NumberFormat('fr-FR').format(amount) + ' FCFA';
}

/**
 * Valider un numéro de téléphone
 */
function validatePhone(phone) {
    const phoneRegex = /^[0-9]{10}$/;
    return phoneRegex.test(phone);
}

/**
 * Afficher un loader sur un bouton
 */
function showButtonLoader(button) {
    const originalContent = button.innerHTML;
    button.setAttribute('data-original-content', originalContent);
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Chargement...';
}

/**
 * Masquer le loader d'un bouton
 */
function hideButtonLoader(button) {
    const originalContent = button.getAttribute('data-original-content');
    button.disabled = false;
    button.innerHTML = originalContent;
}
