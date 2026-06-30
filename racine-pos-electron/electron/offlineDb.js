/**
 * offlineDb.js — SQLite-based offline store for the Electron main process.
 *
 * Uses better-sqlite3 (synchronous, fast, no native rebuild headaches with
 * Electron). All access from the renderer goes through IPC channels exposed
 * in preload.js and handled in main.js.
 *
 * DB file location: {userData}/racine-pos-offline.db
 *
 * Table: pending_orders
 *   id          INTEGER PRIMARY KEY AUTOINCREMENT
 *   offline_id  TEXT    UNIQUE  (uuid v4)
 *   payload     TEXT            (JSON-serialised order data)
 *   created_at  TEXT            (ISO 8601)
 *   synced      INTEGER DEFAULT 0  (0 = pending, 1 = synced)
 *   synced_at   TEXT    DEFAULT NULL
 */

const path = require('path');
const crypto = require('crypto');

let db = null;

// ---------------------------------------------------------------------------
// Initialisation
// ---------------------------------------------------------------------------

/**
 * Open (or create) the SQLite database at the Electron userData path.
 *
 * @param {string} userDataPath  app.getPath('userData')
 */
function init(userDataPath) {
  // better-sqlite3 is a synchronous driver — require lazily so the module
  // only needs to be present at runtime, not at parse time during tests.
  const Database = require('better-sqlite3');

  const dbPath = path.join(userDataPath, 'racine-pos-offline.db');
  db = new Database(dbPath);

  // WAL mode for better concurrent read performance
  db.pragma('journal_mode = WAL');

  db.exec(`
    CREATE TABLE IF NOT EXISTS pending_orders (
      id          INTEGER PRIMARY KEY AUTOINCREMENT,
      offline_id  TEXT    UNIQUE NOT NULL,
      payload     TEXT    NOT NULL,
      created_at  TEXT    NOT NULL,
      synced      INTEGER DEFAULT 0,
      synced_at   TEXT    DEFAULT NULL
    );
  `);

  // Index for common queries
  db.exec(`
    CREATE INDEX IF NOT EXISTS idx_pending_orders_synced
    ON pending_orders (synced);
  `);
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function generateUUID() {
  return crypto.randomUUID();
}

function ensureDb() {
  if (!db) {
    throw new Error('offlineDb: database not initialised — call init() first');
  }
}

// ---------------------------------------------------------------------------
// CRUD Operations
// ---------------------------------------------------------------------------

/**
 * Save a pending order to the local SQLite store.
 *
 * @param {object} payload  Full order payload (will be JSON-serialised)
 * @returns {{ offline_id: string, created_at: string }}
 */
function savePendingOrder(payload) {
  ensureDb();

  const offlineId = (payload && payload.offline_id) || generateUUID();
  const createdAt = new Date().toISOString();

  const stmt = db.prepare(`
    INSERT OR IGNORE INTO pending_orders (offline_id, payload, created_at, synced)
    VALUES (?, ?, ?, 0)
  `);

  stmt.run(offlineId, JSON.stringify(payload), createdAt);

  return { offline_id: offlineId, created_at: createdAt };
}

/**
 * Get all orders that have not been synced yet.
 *
 * @returns {Array<{ id: number, offline_id: string, payload: object, created_at: string }>}
 */
function getPendingOrders() {
  ensureDb();

  const rows = db.prepare(`
    SELECT id, offline_id, payload, created_at
    FROM pending_orders
    WHERE synced = 0
    ORDER BY created_at ASC
  `).all();

  return rows.map((row) => ({
    ...row,
    payload: JSON.parse(row.payload),
  }));
}

/**
 * Mark one or more orders as synced.
 *
 * @param {string[]} offlineIds  Array of offline_id values to mark
 * @returns {number} Number of rows updated
 */
function markSynced(offlineIds) {
  ensureDb();

  if (!Array.isArray(offlineIds) || offlineIds.length === 0) return 0;

  const syncedAt = new Date().toISOString();

  // Use a transaction for atomicity
  const markOne = db.prepare(`
    UPDATE pending_orders
    SET synced = 1, synced_at = ?
    WHERE offline_id = ? AND synced = 0
  `);

  const markMany = db.transaction((ids) => {
    let updated = 0;
    for (const id of ids) {
      const result = markOne.run(syncedAt, id);
      updated += result.changes;
    }
    return updated;
  });

  return markMany(offlineIds);
}

/**
 * Get statistics about the offline store.
 *
 * @returns {{ pending: number, synced: number, total: number }}
 */
function getStats() {
  ensureDb();

  const row = db.prepare(`
    SELECT
      COUNT(*)                          AS total,
      SUM(CASE WHEN synced = 0 THEN 1 ELSE 0 END) AS pending,
      SUM(CASE WHEN synced = 1 THEN 1 ELSE 0 END) AS synced
    FROM pending_orders
  `).get();

  return {
    pending: row.pending || 0,
    synced: row.synced || 0,
    total: row.total || 0,
  };
}

/**
 * Delete synced orders older than N days.
 *
 * @param {number} olderThanDays  Number of days (default 7)
 * @returns {number} Number of rows deleted
 */
function cleanup(olderThanDays = 7) {
  ensureDb();

  const cutoff = new Date(Date.now() - olderThanDays * 24 * 60 * 60 * 1000).toISOString();

  const result = db.prepare(`
    DELETE FROM pending_orders
    WHERE synced = 1 AND synced_at < ?
  `).run(cutoff);

  return result.changes;
}

/**
 * Close the database connection gracefully.
 */
function close() {
  if (db) {
    db.close();
    db = null;
  }
}

module.exports = {
  init,
  savePendingOrder,
  getPendingOrders,
  markSynced,
  getStats,
  cleanup,
  close,
};
