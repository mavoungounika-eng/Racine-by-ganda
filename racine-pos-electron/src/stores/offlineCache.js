import { openDB } from 'idb';

const DB_NAME  = 'racine_pos_offline';
const DB_VER   = 1;

function db() {
  return openDB(DB_NAME, DB_VER, {
    upgrade(db) {
      if (!db.objectStoreNames.contains('auth'))     db.createObjectStore('auth');
      if (!db.objectStoreNames.contains('products')) db.createObjectStore('products', { keyPath: 'id' });
    },
  });
}

function serializable(value) {
  if (value == null) return value;
  return JSON.parse(JSON.stringify(value));
}

/* ── Auth ─────────────────────────────────────────────────── */
export async function saveOfflineAuth(email, password, operator, operatorToken, deviceToken) {
  const d = await db();
  await d.put('auth', {
    email,
    hash: btoa(email + ':' + password),
    operator: serializable(operator),
    operatorToken: serializable(operatorToken),
    deviceToken,
  }, 'session');
}

export async function loadOfflineAuth() {
  const d = await db();
  return d.get('auth', 'session');
}

export async function verifyOfflineAuth(email, password) {
  const cached = await loadOfflineAuth();
  if (!cached) return null;
  if (cached.email === email && cached.hash === btoa(email + ':' + password)) {
    return { operator: cached.operator, token: cached.operatorToken, deviceToken: cached.deviceToken };
  }
  return null;
}

export async function clearOfflineAuth() {
  const d = await db();
  await d.delete('auth', 'session');
}

/* ── Products ─────────────────────────────────────────────── */
export async function saveOfflineProducts(products) {
  const d = await db();
  const tx = d.transaction('products', 'readwrite');
  await tx.store.clear();
  await Promise.all(products.map(p => tx.store.put(p)));
  await tx.done;
}

export async function loadOfflineProducts() {
  const d = await db();
  return d.getAll('products');
}

/* ── Session ──────────────────────────────────────────────── */
export async function saveOfflineSession(session) {
  const d = await db();
  const clean = serializable(session);
  await d.put('auth', { session: clean }, 'current_session');
}

export async function loadOfflineSession() {
  const d = await db();
  const rec = await d.get('auth', 'current_session');
  return rec?.session || null;
}
