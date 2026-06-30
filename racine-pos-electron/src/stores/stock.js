import { defineStore } from 'pinia'
import { echo } from '@/plugins/echo'

/**
 * Stock store temps réel — alimenté par les WebSocket Reverb.
 *
 * Abonné à :
 *   - private-pos.broadcast : toutes les mises à jour stock (multi-caisses)
 *   - private-stock.{id}    : produit spécifique (si requis)
 *
 * Fallback gracieux : si echo est null (Reverb indisponible),
 * le POS continue normalement sans WS.
 */
export const useStockStore = defineStore('stock', {
  state: () => ({
    /** Map  productId(int) → { stock, name, lastUpdated } */
    products: new Map(),
    /** Alertes stock bas + épuisement */
    alerts: [],
    /** Anomalies détectées */
    anomalies: [],
    /** WebSocket opérationnel */
    isConnected: false,
    /** Dernier timestamp reçu */
    lastUpdate: null,
    /** Canaux ouverts (pour cleanup) */
    _channels: [],
  }),

  getters: {
    /** Nombre d'alertes actives */
    alertCount: (state) => state.alerts.length,
    /** Alertes de type stock-bas en priorité */
    criticalAlerts: (state) => state.alerts.filter(a => a.type === 'out_of_stock'),
  },

  actions: {
    /**
     * Initialiser — à appeler après login et chargement produits.
     * @param {number[]} productIds - IDs à surveiller individuellement (optionnel)
     */
    async init(productIds = []) {
      if (!echo) {
        console.warn('[StockStore] Echo non disponible — mode offline stock')
        return
      }

      try {
        this._subscribeToMulticast()
        for (const id of productIds) {
          this._subscribeToProduct(id)
        }
        this.isConnected = true
        console.info('[StockStore] WebSocket stock initialisé')
      } catch (err) {
        console.warn('[StockStore] Échec initialisation WS (POS continue):', err)
      }
    },

    /** Canal multi-caisses — toutes les mises à jour stock */
    _subscribeToMulticast() {
      try {
        const channel = echo.private('pos.broadcast')
          .listen('.stock.decremented', (data) => this._handleStockDecremented(data))
          .listen('.stock.low_alert', (data) => this._handleStockLowAlert(data))

        this._channels.push('pos.broadcast')
        return channel
      } catch (err) {
        console.warn('[StockStore] Échec abonnement pos.broadcast:', err)
      }
    },

    /** Canal par produit — pour surveillance ciblée */
    _subscribeToProduct(productId) {
      const channelName = `stock.${productId}`
      if (this._channels.includes(channelName)) return

      try {
        echo.private(channelName)
          .listen('.stock.decremented', (data) => this._handleStockDecremented(data))

        this._channels.push(channelName)
      } catch (err) {
        console.warn(`[StockStore] Échec abonnement ${channelName}:`, err)
      }
    },

    /** Traitement mise à jour stock */
    _handleStockDecremented(data) {
      const current = this.products.get(data.product_id) ?? {}
      this.products.set(data.product_id, {
        ...current,
        stock: data.stock_after,
        name: data.product_name,
        lastUpdated: data.timestamp,
        source: data.source,
        updatedByDevice: data.updated_by_device,
      })
      this.lastUpdate = data.timestamp

      // Alerte si stock épuisé
      if (data.stock_after === 0) {
        this._addAlert({
          type: 'out_of_stock',
          product_id: data.product_id,
          product_name: data.product_name,
          message: `${data.product_name} est épuisé`,
          timestamp: data.timestamp,
        })
      }
    },

    /** Traitement alerte stock bas */
    _handleStockLowAlert(data) {
      const existingIndex = this.alerts.findIndex(
        a => a.product_id === data.product_id && a.type === 'low_stock'
      )
      const alert = {
        type: 'low_stock',
        product_id: data.product_id,
        product_name: data.product_name,
        current_stock: data.current_stock,
        threshold: data.threshold,
        source: data.source,
        timestamp: data.timestamp,
      }
      if (existingIndex !== -1) {
        // Mettre à jour le stock actuel dans l'alerte existante
        this.alerts[existingIndex] = alert
      } else {
        this._addAlert(alert)
      }
    },

    _addAlert(alert) {
      this.alerts.unshift(alert) // Newest first
      // Limiter la liste à 50 alertes
      if (this.alerts.length > 50) this.alerts.pop()
    },

    // ─── API publique ─────────────────────────────────────────────────

    /** Stock connu d'un produit (null si pas encore reçu via WS) */
    getStock(productId) {
      return this.products.get(productId)?.stock ?? null
    },

    /** Supprimer une alerte */
    clearAlert(productId) {
      this.alerts = this.alerts.filter(a => a.product_id !== productId)
    },

    /** Supprimer toutes les alertes */
    clearAllAlerts() {
      this.alerts = []
    },

    /** Nettoyage à la déconnexion */
    cleanup() {
      if (!echo) return
      for (const channelName of this._channels) {
        try { echo.leave(channelName) } catch {}
      }
      this._channels = []
      this.isConnected = false
    },
  },
})
