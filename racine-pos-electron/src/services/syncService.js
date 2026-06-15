import OfflineStore from './offlineStore';

/**
 * SyncService — synchronisation des ventes offline vers le backend.
 *
 * Vit côté RENDERER (comme toute la logique métier du POS — cf. commentaire
 * de electron/preload.js). Contrat avec ApiService (src/services/api.js,
 * fourni par ailleurs) :
 *
 *   ApiService.syncOfflineOrders(orders)
 *     → POST /api/pos/sync
 *     → { success, data: { synced_ids: [...], skipped_ids: [...] } }
 *
 * Idempotence : les offline_id présents dans synced_ids OU skipped_ids
 * (déjà connus du serveur) sont marqués synced localement.
 *
 * Déclencheurs :
 *  - init() au chargement du module (auto, hors environnement de test)
 *    → sync immédiat si navigator.onLine
 *  - événement window 'online' (reconnexion réseau renderer)
 *  - CustomEvent window 'network:online' (émis par electron/syncBootstrap.js
 *    depuis le main process via net.isOnline(), ou par tout consommateur de
 *    networkMonitor.js)
 *
 * Événement émis après chaque passe ayant traité des ventes :
 *  - CustomEvent 'sync:complete' avec detail = { synced, failed }
 */

const RECONNECT_DEBOUNCE_MS = 2000;

class SyncService {
  constructor() {
    this._syncing = false;
    this._initialized = false;
    this._reconnectTimer = null;
    this._onOnline = this._handleOnline.bind(this);
  }

  /**
   * Synchronise toutes les ventes pending.
   * Anti-réentrance : une seule sync à la fois (les appels concurrents
   * retournent immédiatement { synced: 0, failed: 0, skipped: true }).
   *
   * @returns {Promise<{synced:number, failed:number, skipped?:boolean}>}
   */
  async sync() {
    if (this._syncing) {
      return { synced: 0, failed: 0, skipped: true };
    }
    this._syncing = true;

    try {
      const pending = await OfflineStore.getPendingOrders();
      if (pending.length === 0) {
        return { synced: 0, failed: 0 };
      }

      const orders = pending.map((row) => ({
        offline_id: row.offline_id,
        created_at: row.created_at,
        ...row.payload,
      }));

      const api = await this._resolveApi();
      const res = await api.syncOfflineOrders(orders);

      if (!res?.success) {
        return { synced: 0, failed: pending.length };
      }

      // synced_ids ET skipped_ids = confirmés côté serveur (idempotence)
      const confirmed = new Set([
        ...(res.data?.synced_ids ?? []),
        ...(res.data?.skipped_ids ?? []),
      ]);

      const toMark = pending
        .map((row) => row.offline_id)
        .filter((id) => confirmed.has(id));

      await OfflineStore.markSynced(toMark);

      const result = {
        synced: toMark.length,
        failed: pending.length - toMark.length,
      };
      this._emitComplete(result);
      return result;
    } catch (err) {
      // Erreur réseau ou ApiService indisponible — les ventes restent
      // pending, elles repartiront à la prochaine reconnexion.
      // eslint-disable-next-line no-console
      console.warn('[syncService] sync échouée :', err?.message || err);
      const failed = await OfflineStore.countPending().catch(() => 0);
      return { synced: 0, failed };
    } finally {
      this._syncing = false;
    }
  }

  /**
   * Branche les déclencheurs (démarrage + reconnexion). Idempotent.
   */
  init() {
    if (this._initialized || typeof window === 'undefined') return;
    this._initialized = true;

    // Reconnexion réseau détectée par le renderer (Chromium)
    window.addEventListener('online', this._onOnline);
    // Reconnexion détectée côté main process (electron/syncBootstrap.js)
    // ou relayée par un consommateur de services/networkMonitor.js
    window.addEventListener('network:online', this._onOnline);

    // Sync au démarrage si on est déjà online
    if (typeof navigator === 'undefined' || navigator.onLine) {
      this._scheduleSync();
    }
  }

  /** Détache les listeners (tests / teardown). */
  stop() {
    if (!this._initialized || typeof window === 'undefined') return;
    window.removeEventListener('online', this._onOnline);
    window.removeEventListener('network:online', this._onOnline);
    clearTimeout(this._reconnectTimer);
    this._reconnectTimer = null;
    this._initialized = false;
  }

  // ────────────────────────────────────────────────────────────
  // Internes
  // ────────────────────────────────────────────────────────────

  /** @private Debounce les rafales online/network:online. */
  _handleOnline() {
    this._scheduleSync();
  }

  /** @private */
  _scheduleSync() {
    clearTimeout(this._reconnectTimer);
    this._reconnectTimer = setTimeout(() => {
      this.sync();
    }, RECONNECT_DEBOUNCE_MS);
  }

  /**
   * @private
   * Import dynamique d'ApiService (créé en parallèle dans api.js) pour
   * éviter tout couplage de chargement ; supporte export nommé ou default.
   */
  async _resolveApi() {
    const mod = await import('./api.js');
    const api = mod.ApiService ?? mod.default;
    if (!api || typeof api.syncOfflineOrders !== 'function') {
      throw new Error('ApiService.syncOfflineOrders indisponible');
    }
    return api;
  }

  /** @private Émet sync:complete vers l'UI (badge, banner, stores). */
  _emitComplete({ synced, failed }) {
    if (typeof window === 'undefined') return;
    window.dispatchEvent(
      new CustomEvent('sync:complete', { detail: { synced, failed } }),
    );
  }
}

const syncService = new SyncService();

// Auto-init au chargement du module dans le renderer (pas en test vitest).
// Pour activer la sync au boot, il suffit d'importer ce module une fois
// (ex. `import './services/syncService'` dans src/main.js).
if (typeof window !== 'undefined' && !import.meta.env?.VITEST) {
  syncService.init();
}

export default syncService;
