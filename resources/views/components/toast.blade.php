<!-- Toast Notification Container -->
<div id="toast-container" class="toast-container"></div>

<script>
function showNotification(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    // Icône selon le type
    const icon = type === 'success' ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-exclamation-circle"></i>';
    
    toast.innerHTML = `
        <div class="toast-content">
            <span class="toast-icon">${icon}</span>
            <span class="toast-message">${message}</span>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(toast);
    
    // Animation d'entrée
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    // Auto-suppression après 4 secondes
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 300);
    }, 4000);
}
</script>

<style>
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    pointer-events: none;
}

.toast {
    background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
    color: white;
    padding: 1rem 1.25rem;
    border-radius: 12px;
    min-width: 300px;
    max-width: 400px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    opacity: 0;
    transform: translateX(120%);
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    pointer-events: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    border-left: 4px solid;
}

.toast.show {
    opacity: 1;
    transform: translateX(0);
}

.toast-success {
    border-left-color: #D4A574;
}

.toast-error {
    border-left-color: #E53E3E;
}

.toast-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
}

.toast-icon {
    font-size: 1.25rem;
    color: #D4A574;
}

.toast-error .toast-icon {
    color: #E53E3E;
}

.toast-message {
    font-size: 0.95rem;
    line-height: 1.4;
    font-weight: 500;
}

.toast-close {
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.7);
    cursor: pointer;
    padding: 0.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s;
    font-size: 0.9rem;
}

.toast-close:hover {
    color: white;
}

/* Responsive */
@media (max-width: 768px) {
    .toast-container {
        top: 10px;
        right: 10px;
        left: 10px;
    }
    
    .toast {
        min-width: auto;
        max-width: 100%;
    }
}
</style>


