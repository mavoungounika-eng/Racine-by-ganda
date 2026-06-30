// Setup global pour vitest : installe fake-indexeddb en tant que polyfill
// (définit window.indexedDB et IDBKeyRange). Doit être importé AVANT tout
// module qui appelle openDB().
//
// On fournit également un stub minimal pour `crypto.randomUUID` si l'env
// Node ne l'a pas (Node < 19). Node 20+ expose `crypto.randomUUID` via
// globalThis.crypto — donc normalement no-op.
import 'fake-indexeddb/auto';

if (typeof globalThis.crypto === 'undefined' || typeof globalThis.crypto.randomUUID !== 'function') {
  // Fallback ultra-simple pour tests. NE PAS utiliser en prod (non-crypto).
  globalThis.crypto = {
    ...(globalThis.crypto || {}),
    randomUUID: () =>
      'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = (Math.random() * 16) | 0;
        const v = c === 'x' ? r : (r & 0x3) | 0x8;
        return v.toString(16);
      }),
  };
}
