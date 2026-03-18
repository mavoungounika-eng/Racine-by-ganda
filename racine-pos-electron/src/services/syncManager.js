import { ref } from 'vue'

class SyncManager {
  constructor(apiClient, localDb, networkMonitor) {
    this.apiClient = apiClient
    this.localDb = localDb
    this.isSyncing = ref(false)
    this.lastSyncResult = ref(null)

    // Auto-sync on reconnection
    networkMonitor.on('reconnected', () => this.sync())
  }

  async sync() {
    if (this.isSyncing.value) return
    this.isSyncing.value = true

    const pending = await this.localDb.getPendingSales('pending')
    const results = { processed: 0, failed: 0, errors: [] }

    for (const sale of pending) {
      try {
        const response = await this.apiClient.post(
          '/api/pos/sales',
          sale.saleData,
          sale.idempotencyKey
        )
        await this.localDb.markSaleSynced(sale.localId, response.data?.sale?.id || response.sale?.id)
        results.processed++
      } catch (error) {
        // If it's a network error, stop sync block and don't mark as failed
        if (error.isOffline || !error.response || error.response.status >= 500) {
            results.errors.push({ localId: sale.localId, error: error.message, isOffline: true })
            break;
        } else {
            await this.localDb.markSaleFailed(sale.localId, error.message)
            results.failed++
            results.errors.push({ localId: sale.localId, error: error.message })
        }
      }
    }

    await this.localDb.logSync(results)
    this.lastSyncResult.value = results
    this.isSyncing.value = false
    
    await this.localDb.clearSyncedSales()
    
    return results
  }

  async getPendingCount() {
    return this.localDb.getPendingCount()
  }
}

export default SyncManager
