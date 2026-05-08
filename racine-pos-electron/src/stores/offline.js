import { defineStore } from 'pinia';
import { useAuthStore } from './auth';
import { PosApiClient } from '../api/posClient';
import LocalDb from '../services/localDb';

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

function generateUUID() {
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    return crypto.randomUUID();
  }
  // Fallback for older environments
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    const v = c === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
}

const PING_INTERVAL_MS = 15_000; // 15s connectivity poll
const PING_URL = '/api/pos/offline/status';

// ─────────────────────────────────────────────────────────────────────────────
// Store
// ─────────────────────────────────────────────────────────────────────────────

export const useOfflineStore = defineStore('offline', {
  state: () => ({
    isOffline: false,
    isSyncing: false,

    // Local-DB metrics
    localPendingCount: 0,
    localConflictCount: 0,

    // Sync results
    lastSync: null,
    lastSyncResult: null,

    // Conflicts awaiting manual resolution
    conflicts: [],

    // Server-side queue info
    queueCount: 0,
    serverQueue: [],

    // Internal handles
    _pingTimer: null,
  }),

  getters: {
    hasConflicts: (state) => state.conflicts.length > 0,
  },

  actions: {
    // ──────────────────────────────────────────────────────────────
    // API Client Factory
    // ──────────────────────────────────────────────────────────────
    _client() {
      const auth = useAuthStore();
      return new PosApiClient(
        () => auth.token,
        (t) => {
          auth.token = t;
          auth.isAuthenticated = !!t;
        },
        (offline) => {
          this.isOffline = offline;
        },
        () => auth.operatorToken,
      );
    },

    // ──────────────────────────────────────────────────────────────
    // Connectivity Detection (periodic ping)
    // ──────────────────────────────────────────────────────────────
    startConnectivityMonitor() {
      if (this._pingTimer) return; // already running
      this._pingTimer = setInterval(() => this.checkConnectivity(), PING_INTERVAL_MS);
      // Run immediately on startup
      this.checkConnectivity();
    },

    stopConnectivityMonitor() {
      if (this._pingTimer) {
        clearInterval(this._pingTimer);
        this._pingTimer = null;
      }
    },

    async checkConnectivity() {
      try {
        // PosApiClient.get() retourne déjà res.data axios, donc ici `res` est
        // le body backend `{success, data: {...}, error, meta}`.
        const res = await this._client().get(PING_URL);
        this.queueCount = res?.data?.queue_count ?? 0;

        if (this.isOffline) {
          // Came back online — attempt immediate sync
          this.isOffline = false;
          await this.syncNow();
        }
      } catch (err) {
        // N'active « offline » QUE sur vraie erreur réseau (pas de response
        // HTTP). Un 429 / 401 / 500 signifie que le backend répond — on
        // n'est PAS hors-ligne, même si la requête a échoué.
        if (!err?.response) {
          this.isOffline = true;
        }
      }
    },

    // ──────────────────────────────────────────────────────────────
    // Offline Sale Queueing
    // ──────────────────────────────────────────────────────────────

    /**
     * Queue a sale locally.
     * Called by cart.js when the API call fails due to network issues.
     *
     * @param {object} saleData  items, payment_method, totals, etc.
     * @param {string} [idempotencyKey]
     * @returns {string} The uuid assigned to the sale
     */
    async queueSale(saleData, idempotencyKey = null) {
      const uuid = saleData.uuid || generateUUID();
      const key = idempotencyKey || generateUUID();

      await LocalDb.saveSale({
        ...saleData,
        uuid,
        idempotencyKey: key,
        status: 'pending',
        createdAt: new Date().toISOString(),
      });

      await this._refreshCounts();
      return uuid;
    },

    // Backward-compat shim used by cart.js
    async saveLocalSale(saleData, idempotencyKey) {
      return this.queueSale(saleData, idempotencyKey);
    },

    // ──────────────────────────────────────────────────────────────
    // Sync to Backend
    // ──────────────────────────────────────────────────────────────

    /**
     * Upload all pending local sales to the backend sync endpoint.
     * - Already-synced or conflicted sales are skipped.
     * - Conflicts returned by the backend are recorded locally.
     */
    async syncNow() {
      if (this.isSyncing || this.isOffline) return;

      const pending = await LocalDb.getAllSales('pending');
      if (!pending.length) {
        await this._refreshCounts();
        return;
      }

      this.isSyncing = true;
      try {
        const auth = useAuthStore();
        const payload = {
          device_id: auth.device?.machine_id || 'electron-pos',
          sales: pending.map(({ uuid, items, payment_method, idempotencyKey, customer_name, customer_email, customer_phone }) => ({
            uuid,
            items,
            payment_method,
            idempotency_key: idempotencyKey,
            customer_name: customer_name ?? null,
            customer_email: customer_email ?? null,
            customer_phone: customer_phone ?? null,
          })),
        };

        const res = await this._client().post('/api/pos/offline/sync', payload);
        const result = res?.data ?? res; // PosApiClient returns res.data directly

        // Mark local records
        for (const sale of pending) {
          const isConflict = (result.conflicts ?? []).some(
            (c) => c.sale_uuid === sale.uuid,
          );
          if (isConflict) {
            const conflict = result.conflicts.find((c) => c.sale_uuid === sale.uuid);
            await LocalDb.markSaleConflict(sale.uuid, conflict);
          } else {
            await LocalDb.markSaleSynced(sale.uuid);
          }
        }

        // Update conflict list in store
        this.conflicts = await LocalDb.getAllSales('conflict');
        this.lastSync = new Date().toISOString();
        this.lastSyncResult = result;

        await LocalDb.logSync(result);
      } catch (err) {
        if (!err.response) {
          // Network error — stay offline
          this.isOffline = true;
        }
      } finally {
        this.isSyncing = false;
        await this._refreshCounts();
      }
    },

    // ──────────────────────────────────────────────────────────────
    // Conflict Resolution
    // ──────────────────────────────────────────────────────────────

    /**
     * Resolve a conflict on the backend.
     *
     * @param {string} saleUuid         UUID of the conflicting sale
     * @param {'force_apply'|'discard'} resolution
     */
    async resolveConflict(saleUuid, resolution) {
      await this._client().post('/api/pos/offline/conflict/resolve', {
        sale_uuid: saleUuid,
        resolution,
      });

      // Remove from local DB regardless of resolution
      await LocalDb.markSaleSynced(saleUuid);
      this.conflicts = this.conflicts.filter((c) => c.uuid !== saleUuid);
      await this._refreshCounts();
    },

    // ──────────────────────────────────────────────────────────────
    // Server Queue (legacy endpoints)
    // ──────────────────────────────────────────────────────────────

    async checkStatus() {
      try {
        const res = await this._client().get('/api/pos/offline/status');
        // `res` est le body backend `{success, data: {offline, queue_count}, ...}`.
        this.isOffline = !!res?.data?.offline;
        this.queueCount = res?.data?.queue_count || 0;
        return res;
      } catch (err) {
        // Idem checkConnectivity : seul un VRAI manque de réseau doit flip
        // l'état offline.
        if (!err?.response) {
          this.isOffline = true;
        }
      }
    },

    async flushQueue() {
      const res = await this._client().post('/api/pos/offline/queue/flush');
      this.lastSync = new Date().toISOString();
      return res;
    },

    async getQueue() {
      // `res` est déjà le body (cf. checkConnectivity).
      // Enveloppe backend : {success, data: [...queue], error, meta}.
      const res = await this._client().get('/api/pos/offline/queue');
      this.serverQueue = Array.isArray(res?.data) ? res.data : [];
      return res;
    },

    async loadConflicts() {
      this.conflicts = await LocalDb.getAllSales('conflict');
    },

    // ──────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────

    async _refreshCounts() {
      this.localPendingCount = await LocalDb.getPendingCount();
      this.localConflictCount = await LocalDb.getConflictCount();
    },

    markOffline() {
      this.isOffline = true;
    },
    markOnline() {
      this.isOffline = false;
    },

    // ──────────────────────────────────────────────────────────────
    // Initialisation
    // ──────────────────────────────────────────────────────────────

    async init() {
      await this._refreshCounts();
      await this.loadConflicts();
      this.startConnectivityMonitor();
    },
  },
});
