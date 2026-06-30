/**
 * POS Offline Mode Manager
 * 
 * Gère la détection de perte de connexion et la synchronisation
 * des ventes en mode offline pour le système POS.
 * 
 * FONCTIONNALITÉS:
 * - Détection automatique offline/online
 * - Queue localStorage pour ventes offline
 * - Sync automatique au retour online
 * - Notification visuelle de l'état
 */

class PosOfflineManager {
    constructor() {
        this.isOnline = navigator.onLine;
        this.offlineQueue = this.loadQueue();
        this.syncInProgress = false;

        this.init();
    }

    /**
     * Initialiser les listeners
     */
    init() {
        // Listeners réseau
        window.addEventListener('online', () => this.handleOnline());
        window.addEventListener('offline', () => this.handleOffline());

        // Vérifier état initial
        this.updateUI();

        // Sync au chargement si online
        if (this.isOnline && this.offlineQueue.length > 0) {
            this.syncQueue();
        }
    }

    /**
     * Gérer passage offline
     */
    handleOffline() {
        this.isOnline = false;
        this.updateUI();
        console.warn('[POS] Mode offline activé');
    }

    /**
     * Gérer retour online
     */
    handleOnline() {
        this.isOnline = true;
        this.updateUI();
        console.info('[POS] Connexion rétablie');

        // Sync automatique
        if (this.offlineQueue.length > 0) {
            this.syncQueue();
        }
    }

    /**
     * Mettre à jour l'interface
     */
    updateUI() {
        const banner = document.getElementById('offline-banner');

        if (!this.isOnline) {
            // Afficher banner offline
            if (!banner) {
                this.createOfflineBanner();
            }
        } else {
            // Masquer banner
            if (banner) {
                banner.remove();
            }
        }

        // Mettre à jour compteur queue
        this.updateQueueCounter();
    }

    /**
     * Créer banner offline
     */
    createOfflineBanner() {
        const banner = document.createElement('div');
        banner.id = 'offline-banner';
        banner.className = 'offline-banner';
        banner.innerHTML = `
            <div class="offline-content">
                <i class="fas fa-wifi-slash"></i>
                <span>Mode Offline - Les ventes seront synchronisées automatiquement</span>
                <span id="queue-counter" class="queue-counter">0 en attente</span>
            </div>
        `;

        document.body.insertBefore(banner, document.body.firstChild);
    }

    /**
     * Mettre à jour compteur queue
     */
    updateQueueCounter() {
        const counter = document.getElementById('queue-counter');
        if (counter) {
            const count = this.offlineQueue.length;
            counter.textContent = `${count} en attente`;
            counter.style.display = count > 0 ? 'inline' : 'none';
        }
    }

    /**
     * Ajouter vente à la queue offline
     */
    addToQueue(orderData) {
        const queueItem = {
            id: 'offline_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
            timestamp: new Date().toISOString(),
            data: orderData,
            attempts: 0,
            maxAttempts: 3
        };

        this.offlineQueue.push(queueItem);
        this.saveQueue();
        this.updateQueueCounter();

        console.info('[POS] Vente ajoutée à la queue offline', queueItem.id);
        return queueItem.id;
    }

    /**
     * Synchroniser la queue
     */
    async syncQueue() {
        if (this.syncInProgress || this.offlineQueue.length === 0) {
            return;
        }

        this.syncInProgress = true;
        console.info('[POS] Début synchronisation queue', this.offlineQueue.length);

        const failedItems = [];

        for (const item of this.offlineQueue) {
            try {
                await this.syncItem(item);
                console.info('[POS] Item synchronisé', item.id);
            } catch (error) {
                console.error('[POS] Échec sync item', item.id, error);

                item.attempts++;
                if (item.attempts < item.maxAttempts) {
                    failedItems.push(item);
                } else {
                    console.error('[POS] Item abandonné après max attempts', item.id);
                    this.logFailedItem(item, error);
                }
            }
        }

        // Mettre à jour queue avec items échoués
        this.offlineQueue = failedItems;
        this.saveQueue();
        this.updateQueueCounter();

        this.syncInProgress = false;

        // Notification succès
        if (failedItems.length === 0) {
            this.showNotification('Toutes les ventes ont été synchronisées !', 'success');
        } else {
            this.showNotification(`${failedItems.length} ventes n'ont pas pu être synchronisées`, 'warning');
        }
    }

    /**
     * Synchroniser un item
     */
    async syncItem(item) {
        const response = await fetch('/admin/pos/create-order', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Offline-Sync': 'true',
                'X-Offline-ID': item.id
            },
            body: JSON.stringify(item.data)
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || 'Sync failed');
        }

        return result;
    }

    /**
     * Logger item échoué
     */
    logFailedItem(item, error) {
        // Envoyer à un endpoint de logging
        fetch('/admin/pos/log-failed-sync', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                offline_id: item.id,
                data: item.data,
                error: error.message,
                attempts: item.attempts
            })
        }).catch(err => {
            console.error('[POS] Failed to log failed item', err);
        });
    }

    /**
     * Charger queue depuis localStorage
     */
    loadQueue() {
        try {
            const stored = localStorage.getItem('pos_offline_queue');
            return stored ? JSON.parse(stored) : [];
        } catch (error) {
            console.error('[POS] Error loading queue', error);
            return [];
        }
    }

    /**
     * Sauvegarder queue dans localStorage
     */
    saveQueue() {
        try {
            localStorage.setItem('pos_offline_queue', JSON.stringify(this.offlineQueue));
        } catch (error) {
            console.error('[POS] Error saving queue', error);
        }
    }

    /**
     * Afficher notification
     */
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `pos-notification pos-notification-${type}`;
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.classList.add('show');
        }, 10);

        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    /**
     * Vérifier si online
     */
    checkOnline() {
        return this.isOnline;
    }

    /**
     * Obtenir taille queue
     */
    getQueueSize() {
        return this.offlineQueue.length;
    }
}

// Styles CSS pour le mode offline
const offlineStyles = `
.offline-banner {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
    color: white;
    padding: 1rem;
    z-index: 9999;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    animation: slideDown 0.3s ease-out;
}

.offline-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: 1rem;
    font-weight: 600;
}

.offline-content i {
    font-size: 1.5rem;
}

.queue-counter {
    margin-left: auto;
    background: rgba(255, 255, 255, 0.2);
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
}

.pos-notification {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    color: white;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.3s ease-out;
    z-index: 9998;
}

.pos-notification.show {
    opacity: 1;
    transform: translateY(0);
}

.pos-notification-success {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
}

.pos-notification-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.pos-notification-error {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

@keyframes slideDown {
    from {
        transform: translateY(-100%);
    }
    to {
        transform: translateY(0);
    }
}
`;

// Injecter styles
const styleSheet = document.createElement('style');
styleSheet.textContent = offlineStyles;
document.head.appendChild(styleSheet);

// Initialiser manager
const posOfflineManager = new PosOfflineManager();

// Exporter pour utilisation globale
window.posOfflineManager = posOfflineManager;
