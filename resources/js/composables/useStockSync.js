import { ref, onUnmounted } from 'vue'

/**
 * useStockSync — Composable Vue pour sync stock temps réel (Web Admin).
 *
 * Usage :
 *   // Dans un composant admin
 *   const { isConnected, stockUpdates, alerts, watchProduct, watchAdminStock } = useStockSync()
 *   watchProduct(42, (data) => console.log('Stock produit 42 :', data.stock_after))
 *   watchAdminStock({
 *     onLowAlert: (data) => toast.warning(`Stock bas — ${data.product_name}`),
 *     onAnomaly : (data) => toast.error('Anomalie stock !'),
 *   })
 */
export function useStockSync() {
  const isConnected  = ref(false)
  const stockUpdates = ref([])
  const alerts       = ref([])
  const _channels    = []

  /**
   * Écouter les mises à jour stock d'un produit spécifique.
   * Se désabonne automatiquement au démontage du composant.
   */
  function watchProduct(productId, callback) {
    if (!window.Echo) {
      console.warn('[useStockSync] window.Echo non disponible')
      return
    }

    const channelName = `stock.${productId}`

    try {
      window.Echo.private(channelName)
        .listen('.stock.decremented', (data) => {
          stockUpdates.value.unshift(data)
          if (stockUpdates.value.length > 100) stockUpdates.value.pop()
          callback?.(data)
        })

      _channels.push(channelName)
      isConnected.value = true
    } catch (err) {
      console.warn(`[useStockSync] Impossible d'écouter ${channelName}:`, err)
    }

    onUnmounted(() => {
      try { window.Echo?.leave(channelName) } catch {}
    })
  }

  /**
   * Écouter le canal admin (alertes stock bas + anomalies).
   * Se désabonne automatiquement au démontage du composant.
   */
  function watchAdminStock({ onLowAlert, onAnomaly } = {}) {
    if (!window.Echo) return

    try {
      window.Echo.private('admin.stock')
        .listen('.stock.low_alert', (data) => {
          alerts.value.unshift({ type: 'low', ...data })
          onLowAlert?.(data)
        })
        .listen('.stock.anomaly', (data) => {
          alerts.value.unshift({ type: 'anomaly', ...data })
          onAnomaly?.(data)
        })

      _channels.push('admin.stock')
      isConnected.value = true
    } catch (err) {
      console.warn('[useStockSync] Impossible d\'écouter admin.stock:', err)
    }

    onUnmounted(() => {
      try { window.Echo?.leave('admin.stock') } catch {}
    })
  }

  return {
    isConnected,
    stockUpdates,
    alerts,
    watchProduct,
    watchAdminStock,
  }
}
