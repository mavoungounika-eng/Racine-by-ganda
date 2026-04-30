// Tests unitaires pour src/services/localDb.js
//
// Stratégie d'isolation :
// - Chaque test supprime la DB IndexedDB avant de commencer
// - On utilise vi.resetModules() + import dynamique pour obtenir une
//   instance fraîche du singleton LocalDb (sinon le dbPromise hérité
//   pointerait vers une connexion déjà fermée).

import { openDB } from 'idb';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const DB_NAME = 'racine-pos';

/**
 * Registre des instances LocalDb créées pendant les tests. Chaque
 * instance détient une connexion IndexedDB ouverte — qu'il faut
 * fermer explicitement avant `deleteDatabase`, sinon la suppression
 * reste blocked ad vitam.
 */
const openedDbs = [];

/** Ferme toutes les connexions IndexedDB ouvertes par les tests. */
async function closeAllOpened() {
  for (const inst of openedDbs.splice(0)) {
    try {
      const conn = await inst.dbPromise;
      conn.close();
    } catch {
      // instance jamais ouverte — rien à faire
    }
  }
}

async function deleteDb() {
  await closeAllOpened();
  await new Promise((resolve) => {
    const req = indexedDB.deleteDatabase(DB_NAME);
    req.onsuccess = () => resolve();
    req.onerror = () => resolve();
    req.onblocked = () => resolve();
  });
}

/**
 * Retourne une NOUVELLE instance LocalDb avec un dbPromise frais.
 * Utilise vi.resetModules() + import dynamique pour contourner le
 * fait que `localDb.js` exporte un singleton.
 */
async function freshLocalDb() {
  await deleteDb();
  vi.resetModules();
  const mod = await import('../../src/services/localDb.js');
  openedDbs.push(mod.default);
  return mod.default;
}

beforeEach(async () => {
  await deleteDb();
});

afterEach(async () => {
  await closeAllOpened();
});

// ──────────────────────────────────────────────────────────────────────
// PENDING SALES — CRUD + idempotence
// ──────────────────────────────────────────────────────────────────────

describe('localDb — saveSale', () => {
  it('persiste une vente et injecte status/createdAt par défaut', async () => {
    const db = await freshLocalDb();
    const uuid = await db.saveSale({ uuid: 'u1', total: 100 });
    expect(uuid).toBe('u1');

    const all = await db.getAllSales('pending');
    expect(all).toHaveLength(1);
    expect(all[0]).toMatchObject({ uuid: 'u1', total: 100, status: 'pending' });
    expect(all[0].createdAt).toBeTruthy();
  });

  it('est idempotent par uuid : la seconde écriture est ignorée', async () => {
    const db = await freshLocalDb();
    await db.saveSale({ uuid: 'u1', total: 100 });
    await db.saveSale({ uuid: 'u1', total: 999 }); // tentative d'écrasement

    const all = await db.getAllSales('pending');
    expect(all).toHaveLength(1);
    expect(all[0].total).toBe(100); // l'original doit être préservé
  });

  it("est idempotent par idempotencyKey : retourne l'uuid d'origine", async () => {
    const db = await freshLocalDb();
    await db.saveSale({ uuid: 'u1', idempotencyKey: 'k1', total: 100 });
    const result = await db.saveSale({ uuid: 'u2', idempotencyKey: 'k1', total: 200 });

    expect(result).toBe('u1');
    expect(await db.getAllSales('pending')).toHaveLength(1);
  });

  it('jette une erreur si uuid manquant', async () => {
    const db = await freshLocalDb();
    await expect(db.saveSale({ total: 100 })).rejects.toThrow(/uuid is required/);
  });

  it('savePendingSale forward idempotencyKey correctement', async () => {
    const db = await freshLocalDb();
    await db.savePendingSale({ uuid: 'u1' }, 'k1');
    const all = await db.getAllSales('pending');
    expect(all[0].idempotencyKey).toBe('k1');
  });
});

describe('localDb — getAllSales', () => {
  it('filtre par status et trie par createdAt ascendant', async () => {
    const db = await freshLocalDb();
    await db.saveSale({ uuid: 'u1', createdAt: '2025-01-02T10:00:00Z' });
    await db.saveSale({ uuid: 'u2', createdAt: '2025-01-01T10:00:00Z' });
    await db.saveSale({ uuid: 'u3', createdAt: '2025-01-03T10:00:00Z', status: 'synced' });

    const pending = await db.getAllSales('pending');
    expect(pending.map((r) => r.uuid)).toEqual(['u2', 'u1']);

    const synced = await db.getAllSales('synced');
    expect(synced.map((r) => r.uuid)).toEqual(['u3']);
  });

  it('retourne les ventes expirées au-delà du cutoff', async () => {
    const db = await freshLocalDb();
    const old = new Date(Date.now() - 48 * 60 * 60 * 1000).toISOString();
    const recent = new Date().toISOString();
    await db.saveSale({ uuid: 'u1', createdAt: old });
    await db.saveSale({ uuid: 'u2', createdAt: recent });

    const expired = await db.getExpiredSales(24);
    expect(expired.map((r) => r.uuid)).toEqual(['u1']);
  });
});

describe('localDb — mutations de statut', () => {
  it('updateSale patch les champs existants', async () => {
    const db = await freshLocalDb();
    await db.saveSale({ uuid: 'u1', total: 100 });
    await db.updateSale('u1', { customerName: 'Alice' });

    const all = await db.getAllSales('pending');
    expect(all[0].customerName).toBe('Alice');
    expect(all[0].total).toBe(100); // champs non-patchés préservés
  });

  it('markSaleSynced déplace le status et horodate', async () => {
    const db = await freshLocalDb();
    await db.saveSale({ uuid: 'u1' });
    await db.markSaleSynced('u1');

    const synced = await db.getAllSales('synced');
    expect(synced).toHaveLength(1);
    expect(synced[0].syncedAt).toBeTruthy();
    expect(await db.getAllSales('pending')).toHaveLength(0);
  });

  it('markSaleConflict attache conflictData', async () => {
    const db = await freshLocalDb();
    await db.saveSale({ uuid: 'u1' });
    await db.markSaleConflict('u1', { reason: 'amount_mismatch' });

    const conflicts = await db.getAllSales('conflict');
    expect(conflicts).toHaveLength(1);
    expect(conflicts[0].conflictData).toEqual({ reason: 'amount_mismatch' });
    expect(conflicts[0].conflictedAt).toBeTruthy();
  });

  it('markSaleFailed enregistre le message erreur', async () => {
    const db = await freshLocalDb();
    await db.saveSale({ uuid: 'u1' });
    await db.markSaleFailed('u1', 'network timeout');

    const failed = await db.getAllSales('failed');
    expect(failed[0].errorMessage).toBe('network timeout');
  });

  it('updateSale sur uuid inexistant est no-op', async () => {
    const db = await freshLocalDb();
    await expect(db.updateSale('ghost', { total: 999 })).resolves.toBeUndefined();
    expect(await db.getAllSales('pending')).toHaveLength(0);
  });
});

describe('localDb — suppressions', () => {
  it('deleteSale retire un enregistrement', async () => {
    const db = await freshLocalDb();
    await db.saveSale({ uuid: 'u1' });
    await db.deleteSale('u1');
    expect(await db.getAllSales('pending')).toHaveLength(0);
  });

  it('clearByStatus supprime uniquement le statut cible', async () => {
    const db = await freshLocalDb();
    await db.saveSale({ uuid: 'u1' });
    await db.saveSale({ uuid: 'u2', status: 'synced' });
    await db.clearByStatus('pending');

    expect(await db.getAllSales('pending')).toHaveLength(0);
    expect(await db.getAllSales('synced')).toHaveLength(1);
  });

  it('clearAll vide le store', async () => {
    const db = await freshLocalDb();
    await db.saveSale({ uuid: 'u1' });
    await db.saveSale({ uuid: 'u2', status: 'synced' });
    await db.clearAll();

    expect(await db.getAllSales('pending')).toHaveLength(0);
    expect(await db.getAllSales('synced')).toHaveLength(0);
  });
});

describe('localDb — compteurs', () => {
  it('getPendingCount et getConflictCount reflètent les index', async () => {
    const db = await freshLocalDb();
    await db.saveSale({ uuid: 'u1' });
    await db.saveSale({ uuid: 'u2', status: 'conflict' });
    await db.saveSale({ uuid: 'u3', status: 'conflict' });

    expect(await db.getPendingCount()).toBe(1);
    expect(await db.getConflictCount()).toBe(2);
  });
});

describe('localDb — sync log', () => {
  it('logSync classe en success vs partial', async () => {
    const db = await freshLocalDb();
    await db.logSync({ synced: 3, failed: 0, conflicts: [] });
    await db.logSync({ synced: 2, failed: 1, conflicts: [] });
    await db.logSync({ synced: 5, failed: 0, conflicts: [{ uuid: 'u1' }] });

    const recent = await db.getRecentSyncs(10);
    expect(recent).toHaveLength(3);
    // getRecentSyncs fait .reverse() — le plus récent en premier
    expect(recent[0].status).toBe('partial'); // conflicts.length > 0
    expect(recent[1].status).toBe('partial'); // failed > 0
    expect(recent[2].status).toBe('success');
  });
});

// ──────────────────────────────────────────────────────────────────────
// MIGRATION v1 → v2
// ──────────────────────────────────────────────────────────────────────
//
// Scénario : avant la refonte keyPath, pending_sales utilisait localId
// autoIncrement. La migration doit préserver les ventes existantes et
// générer un uuid pour celles qui n'en avaient pas.

describe('localDb — migration v1 → v2', () => {
  it('préserve les ventes pending et génère un uuid si absent', async () => {
    // 1. Crée la DB au schéma v1 à la main
    const v1Db = await openDB(DB_NAME, 1, {
      upgrade(db) {
        const store = db.createObjectStore('pending_sales', {
          keyPath: 'localId',
          autoIncrement: true,
        });
        store.createIndex('status', 'status');
        store.createIndex('createdAt', 'createdAt');
        store.createIndex('idempotencyKey', 'idempotencyKey', { unique: true });
        db.createObjectStore('sync_log', { keyPath: 'id', autoIncrement: true });
      },
    });

    // 2. Insère 2 ventes legacy
    await v1Db.add('pending_sales', {
      uuid: 'existing-uuid',
      idempotencyKey: 'k1',
      status: 'pending',
      createdAt: '2025-01-01T10:00:00Z',
      total: 100,
    });
    await v1Db.add('pending_sales', {
      // Pas d'uuid → doit être généré à la migration
      idempotencyKey: 'k2',
      status: 'pending',
      createdAt: '2025-01-02T10:00:00Z',
      total: 200,
    });
    v1Db.close();

    // 3. Import localDb → déclenche l'upgrade v1 → v2
    vi.resetModules();
    const mod = await import('../../src/services/localDb.js');
    const db = mod.default;
    openedDbs.push(db);

    // 4. Les 2 ventes doivent être préservées
    const all = await db.getAllSales('pending');
    expect(all).toHaveLength(2);

    const kept = all.find((r) => r.idempotencyKey === 'k1');
    expect(kept.uuid).toBe('existing-uuid');
    expect(kept.total).toBe(100);
    expect(kept.localId).toBeUndefined(); // l'ancien champ est dropped

    const generated = all.find((r) => r.idempotencyKey === 'k2');
    expect(generated.uuid).toBeTruthy();
    expect(generated.uuid).not.toBe('existing-uuid');
    expect(generated.total).toBe(200);
  });

  it("ne plante pas si v1 store est vide", async () => {
    const v1Db = await openDB(DB_NAME, 1, {
      upgrade(db) {
        const store = db.createObjectStore('pending_sales', {
          keyPath: 'localId',
          autoIncrement: true,
        });
        store.createIndex('status', 'status');
        store.createIndex('createdAt', 'createdAt');
        store.createIndex('idempotencyKey', 'idempotencyKey', { unique: true });
        db.createObjectStore('sync_log', { keyPath: 'id', autoIncrement: true });
      },
    });
    v1Db.close();

    vi.resetModules();
    const mod = await import('../../src/services/localDb.js');
    const db = mod.default;
    expect(await db.getAllSales('pending')).toEqual([]);
  });
});
