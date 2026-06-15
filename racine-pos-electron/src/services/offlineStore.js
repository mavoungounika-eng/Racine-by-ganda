import { openDB } from 'idb';

/**
 * OfflineStore — file d'attente des ventes hors-ligne (pending_orders).
 *
 * CHOIX D'IMPLÉMENTATION (documenté — cf. CLAUDE.md POS) :
 * better-sqlite3 a été écarté : c'est un module natif Node qui ne tourne que
 * dans le main process Electron, or TOUTE la persistance existante du POS
 * (src/services/localDb.js, src/stores/offlineCache.js) vit déjà côté
 * renderer en IndexedDB via le package `idb`, et electron/preload.js
 * documente explicitement que la logique métier ne transite pas par IPC.
 * On réutilise donc le même mécanisme (idb), dans une base DÉDIÉE
 * (`racine-pos-sync`) pour ne pas entrer en conflit de version avec la base
 * `racine-pos` (v2) gérée par localDb.js.
 *
 * Schéma du store `pending_orders` :
 *  - id          : clé primaire auto-incrémentée
 *  - offline_id  : uuid v4, unique (index), identifiant d'idempotence
 *  - payload     : string JSON de la vente
 *  - created_at  : ISO 8601
 *  - synced      : 0 (en attente) | 1 (confirmé serveur)
 */

const DB_NAME = 'racine-pos-sync';
const DB_VERSION = 1;
const STORE = 'pending_orders';

function uuidv4() {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID();
  }
  // Fallback (environnements anciens) — même pattern que stores/offline.js
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    const v = c === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
}

class OfflineStore {
  constructor() {
    this.dbPromise = openDB(DB_NAME, DB_VERSION, {
      upgrade(db) {
        if (!db.objectStoreNames.contains(STORE)) {
          const store = db.createObjectStore(STORE, {
            keyPath: 'id',
            autoIncrement: true,
          });
          store.createIndex('offline_id', 'offline_id', { unique: true });
          store.createIndex('synced', 'synced');
          store.createIndex('created_at', 'created_at');
        }
      },
    });
  }

  /**
   * Enregistre une vente en attente de synchronisation.
   *
   * @param {object} payload  Données de la vente (items, totaux, paiement…)
   * @returns {Promise<string>} L'offline_id (uuid v4) généré
   */
  async savePendingOrder(payload) {
    const db = await this.dbPromise;
    const offlineId = uuidv4();

    await db.add(STORE, {
      offline_id: offlineId,
      payload: JSON.stringify(payload ?? {}),
      created_at: new Date().toISOString(),
      synced: 0,
    });

    return offlineId;
  }

  /**
   * Retourne toutes les ventes NON synchronisées, triées par created_at
   * croissant, payload désérialisé.
   *
   * @returns {Promise<Array<{id:number, offline_id:string, payload:object, created_at:string, synced:number}>>}
   */
  async getPendingOrders() {
    const db = await this.dbPromise;
    const rows = await db.getAllFromIndex(STORE, 'synced', 0);

    return rows
      .sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
      .map((row) => ({
        ...row,
        payload: this._parsePayload(row.payload),
      }));
  }

  /**
   * Marque comme synchronisées les ventes dont l'offline_id est confirmé
   * par le serveur (synced_ids ET skipped_ids — idempotence).
   *
   * @param {string[]} offlineIds
   * @returns {Promise<number>} Nombre de lignes effectivement marquées
   */
  async markSynced(offlineIds) {
    if (!Array.isArray(offlineIds) || offlineIds.length === 0) return 0;

    const db = await this.dbPromise;
    const tx = db.transaction(STORE, 'readwrite');
    const index = tx.store.index('offline_id');
    let marked = 0;

    for (const offlineId of offlineIds) {
      const row = await index.get(offlineId);
      if (row && row.synced !== 1) {
        await tx.store.put({
          ...row,
          synced: 1,
          synced_at: new Date().toISOString(),
        });
        marked += 1;
      }
    }

    await tx.done;
    return marked;
  }

  /**
   * Nombre de ventes en attente de synchronisation.
   *
   * @returns {Promise<number>}
   */
  async countPending() {
    const db = await this.dbPromise;
    return db.countFromIndex(STORE, 'synced', 0);
  }

  /** @private */
  _parsePayload(raw) {
    if (typeof raw !== 'string') return raw ?? {};
    try {
      return JSON.parse(raw);
    } catch {
      return {};
    }
  }
}

export default new OfflineStore();
