import { openDB } from 'idb';

const DB_NAME = 'racine-pos';
const DB_VERSION = 2;

/**
 * LocalDb — Offline-first IndexedDB layer for the POS.
 *
 * Stores:
 *  - pending_sales : sales queued locally while offline
 *  - sync_log      : history of sync attempts
 */
class LocalDb {
  constructor() {
    this.dbPromise = openDB(DB_NAME, DB_VERSION, {
      upgrade: async (db, oldVersion, _newVersion, tx) => {
        // v1 → Create stores
        if (oldVersion < 1) {
          if (!db.objectStoreNames.contains('pending_sales')) {
            const salesStore = db.createObjectStore('pending_sales', {
              keyPath: 'uuid',
            });
            salesStore.createIndex('status', 'status');
            salesStore.createIndex('createdAt', 'createdAt');
            salesStore.createIndex('idempotencyKey', 'idempotencyKey', {
              unique: true,
            });
          }
          if (!db.objectStoreNames.contains('sync_log')) {
            const syncStore = db.createObjectStore('sync_log', {
              keyPath: 'id',
              autoIncrement: true,
            });
            syncStore.createIndex('syncedAt', 'syncedAt');
            syncStore.createIndex('status', 'status');
          }
        }

        // v2 → l'ancien pending_sales utilisait localId autoIncrement ;
        // on migre vers uuid keyPath SANS perdre les ventes pending.
        //
        // Stratégie non-destructive :
        //   1. Lire toutes les ventes existantes (via le vieux store)
        //   2. Drop + recrée le store avec keyPath 'uuid'
        //   3. Re-insère les ventes en garantissant un uuid
        //      (si absent, on en génère un à la volée — cas dev early)
        if (oldVersion === 1 && db.objectStoreNames.contains('pending_sales')) {
          let preserved = [];
          try {
            const oldStore = tx.objectStore('pending_sales');
            preserved = await oldStore.getAll();
          } catch (err) {
            // Si on ne peut pas lire (schéma corrompu), on logge et continue.
            // Mieux vaut un store vide qu'une app qui plante au boot.
            // eslint-disable-next-line no-console
            console.warn('[localDb v1→v2] Impossible de lire pending_sales existant :', err);
            preserved = [];
          }

          db.deleteObjectStore('pending_sales');
          const salesStore = db.createObjectStore('pending_sales', {
            keyPath: 'uuid',
          });
          salesStore.createIndex('status', 'status');
          salesStore.createIndex('createdAt', 'createdAt');
          salesStore.createIndex('idempotencyKey', 'idempotencyKey', {
            unique: true,
          });

          // Ré-insérer les ventes préservées
          for (const row of preserved) {
            const migrated = {
              ...row,
              uuid:
                row.uuid ||
                (typeof crypto !== 'undefined' && crypto.randomUUID
                  ? crypto.randomUUID()
                  : `migrated-${Date.now()}-${Math.random().toString(36).slice(2)}`),
            };
            // Supprimer l'ancien localId autoIncrement s'il traînait
            delete migrated.localId;
            try {
              await salesStore.add(migrated);
            } catch (err) {
              // Si collision idempotencyKey (peu probable), on saute — on privilégie
              // la non-crash au prix d'une vente perdue sur le cas edge.
              // eslint-disable-next-line no-console
              console.warn('[localDb v1→v2] Vente ignorée à la migration :', err, migrated);
            }
          }
          // eslint-disable-next-line no-console
          console.info(`[localDb v1→v2] Migré ${preserved.length} vente(s) pending vers keyPath uuid`);
        }
      },
    });
  }

  // ─────────────────────────────────────────────────────────────
  // PENDING SALES
  // ─────────────────────────────────────────────────────────────

  /**
   * Save a sale locally. Idempotent — won't create a duplicate if
   * a record with the same uuid or idempotencyKey already exists.
   *
   * @param {object} saleData  Full sale payload (must include uuid)
   * @returns {string} The uuid used
   */
  async saveSale(saleData) {
    const db = await this.dbPromise;
    const uuid = saleData.uuid;
    if (!uuid) throw new Error('localDb.saveSale: saleData.uuid is required');

    const tx = db.transaction('pending_sales', 'readwrite');
    const store = tx.objectStore('pending_sales');

    // Idempotency by uuid
    const existing = await store.get(uuid);
    if (existing) {
      await tx.done;
      return uuid;
    }

    // Idempotency by idempotencyKey
    if (saleData.idempotencyKey) {
      const byKey = await store
        .index('idempotencyKey')
        .get(saleData.idempotencyKey);
      if (byKey) {
        await tx.done;
        return byKey.uuid;
      }
    }

    await store.add({
      ...saleData,
      status: saleData.status || 'pending',
      createdAt: saleData.createdAt || new Date().toISOString(),
    });
    await tx.done;
    return uuid;
  }

  /**
   * Backward-compat alias used by cart.js
   */
  async savePendingSale(saleData, idempotencyKey) {
    return this.saveSale({ ...saleData, idempotencyKey });
  }

  /**
   * Return all sales matching the given status (default: 'pending'),
   * sorted by createdAt ascending.
   */
  async getAllSales(status = 'pending') {
    const db = await this.dbPromise;
    const items = await db.getAllFromIndex('pending_sales', 'status', status);
    return items.sort((a, b) => new Date(a.createdAt) - new Date(b.createdAt));
  }

  /** Alias */
  getPendingSales(status = 'pending') {
    return this.getAllSales(status);
  }

  /**
   * Return all sales whose createdAt is older than `maxAgeHours`.
   */
  async getExpiredSales(maxAgeHours = 24) {
    const cutoff = new Date(Date.now() - maxAgeHours * 60 * 60 * 1000);
    const pending = await this.getAllSales('pending');
    return pending.filter((s) => new Date(s.createdAt) < cutoff);
  }

  /**
   * Update the status / extra fields of a sale by uuid.
   */
  async updateSale(uuid, patch) {
    const db = await this.dbPromise;
    const tx = db.transaction('pending_sales', 'readwrite');
    const store = tx.objectStore('pending_sales');
    const item = await store.get(uuid);
    if (item) {
      await store.put({ ...item, ...patch });
    }
    await tx.done;
  }

  async markSaleSynced(uuid) {
    return this.updateSale(uuid, {
      status: 'synced',
      syncedAt: new Date().toISOString(),
    });
  }

  async markSaleConflict(uuid, conflictData) {
    return this.updateSale(uuid, {
      status: 'conflict',
      conflictData,
      conflictedAt: new Date().toISOString(),
    });
  }

  async markSaleFailed(uuid, errorMessage) {
    return this.updateSale(uuid, {
      status: 'failed',
      errorMessage,
      failedAt: new Date().toISOString(),
    });
  }

  /**
   * Permanently remove a sale record by uuid.
   */
  async deleteSale(uuid) {
    const db = await this.dbPromise;
    await db.delete('pending_sales', uuid);
  }

  /**
   * Remove all records matching a given status.
   */
  async clearByStatus(status) {
    const db = await this.dbPromise;
    const tx = db.transaction('pending_sales', 'readwrite');
    const store = tx.objectStore('pending_sales');
    const items = await store.index('status').getAll(status);
    for (const item of items) {
      await store.delete(item.uuid);
    }
    await tx.done;
  }

  /**
   * Remove ALL records from the pending_sales store.
   */
  async clearAll() {
    const db = await this.dbPromise;
    await db.clear('pending_sales');
  }

  async getPendingCount() {
    const db = await this.dbPromise;
    return db.countFromIndex('pending_sales', 'status', 'pending');
  }

  async getConflictCount() {
    const db = await this.dbPromise;
    return db.countFromIndex('pending_sales', 'status', 'conflict');
  }

  // ─────────────────────────────────────────────────────────────
  // SYNC LOG
  // ─────────────────────────────────────────────────────────────

  async logSync(results) {
    const db = await this.dbPromise;
    await db.add('sync_log', {
      ...results,
      syncedAt: new Date().toISOString(),
      status: results.failed > 0 || results.conflicts?.length > 0 ? 'partial' : 'success',
    });
  }

  async getRecentSyncs(limit = 10) {
    const db = await this.dbPromise;
    const all = await db.getAll('sync_log');
    return all.reverse().slice(0, limit);
  }

  // ─────────────────────────────────────────────────────────────
  // COMPAT SHIMS
  // ─────────────────────────────────────────────────────────────
  async clearSyncedSales() {
    const oneDayAgo = new Date(Date.now() - 24 * 60 * 60 * 1000);
    const synced = await this.getAllSales('synced');
    for (const item of synced) {
      if (new Date(item.syncedAt || item.createdAt) < oneDayAgo) {
        await this.deleteSale(item.uuid);
      }
    }
  }
}

export default new LocalDb();
