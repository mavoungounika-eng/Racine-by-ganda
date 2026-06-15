// Preload — exposition minimale vers le renderer.
//
// Le POS renderer (src/) communique directement avec le backend Laravel via
// HTTP (axios, cf. src/api/posClient.js). Aucun besoin d'IPC côté main process
// pour les opérations métier (open-session, create-sale, close-session), donc
// aucun `ipcMain.on`/`ipcMain.handle` n'est enregistré dans electron/main.js.
//
// L'ancien `window.electronAPI` exposait openSession/createSale/closeSession
// mais aucun handler n'était wire côté main et aucun code renderer ne
// l'appelait — retiré pour éviter une fausse surface d'API trompeuse.
//
// À étendre si on ajoute plus tard des fonctions natives :
//   - impression ticket thermique (escpos)
//   - lecteur code-barres USB (HID)
//   - ouverture tiroir-caisse (RJ11 via usb-cashdrawer)
//   - lecteur CB local (PC/SC smartcard)

const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('electron', {
  ping: () => 'pong',
  getMachineId: () => ipcRenderer.invoke('machine-id:get'),
  platform: process.platform, // 'win32' | 'darwin' | 'linux'
  versions: {
    electron: process.versions.electron,
    chrome: process.versions.chrome,
    node: process.versions.node,
  },
  // ── Paiements externes (cf. electron/paymentWindows.js) ──────────────
  // Ouvre une BrowserWindow main-process vers l'URL de paiement Monetbil
  // et résout avec { status: 'success'|'failed'|'cancelled', monetbil_ref? }.
  payments: {
    openMonetbilWindow: (options) => ipcRenderer.invoke('payments:monetbil:open', options),
    closeMonetbilWindow: () => ipcRenderer.invoke('payments:monetbil:close'),
  },

  // ── Offline SQLite Store (better-sqlite3, main process) ──────────────
  //
  // Each method maps to an ipcMain.handle() registered in main.js.
  // The renderer calls these via window.electron.offlineDb.<method>().
  offlineDb: {
    save: (payload) => ipcRenderer.invoke('offlineDb:save', payload),
    getPending: () => ipcRenderer.invoke('offlineDb:getPending'),
    markSynced: (offlineIds) => ipcRenderer.invoke('offlineDb:markSynced', offlineIds),
    stats: () => ipcRenderer.invoke('offlineDb:stats'),
    cleanup: (olderThanDays) => ipcRenderer.invoke('offlineDb:cleanup', olderThanDays),
  },

  // ── Network ────────────────────────────────────────────────────────
  //
  // isOnline() queries Electron's net.isOnline() via the main process.
  // This is a quick OS-level check (does the machine have a network
  // interface up?), NOT a proof that the Laravel backend is reachable.
  // For backend reachability, use the /api/pos/offline/status ping.
  isOnline: () => ipcRenderer.invoke('network:is-online'),

  // ── Printing ──────────────────────────────────────────────────────
  //
  // Silent thermal print via a hidden BrowserWindow in main process.
  // Falls back to browser print dialog if silent printing fails.
  printer: {
    getPrinters: () => ipcRenderer.invoke('printer:get-printers'),
    printReceipt: (opts) => ipcRenderer.invoke('printer:print-receipt', opts),
  },

  // ── Auto-update (electron-updater) ──────────────────────────────
  updater: {
    download: () => ipcRenderer.invoke('update:download'),
    install: () => ipcRenderer.invoke('update:install'),
    onAvailable: (cb) => ipcRenderer.on('update:available', (_e, info) => cb(info)),
    onProgress: (cb) => ipcRenderer.on('update:progress', (_e, p) => cb(p)),
    onDownloaded: (cb) => ipcRenderer.on('update:downloaded', () => cb()),
  },

  // ── Sync events (main → renderer) ───────────────────────────────────
  onSyncComplete: (callback) => {
    ipcRenderer.on('sync:complete', (_event, data) => callback(data));
  },
  onOnlineStatusChanged: (callback) => {
    ipcRenderer.on('network:status-changed', (_event, isOnline) => callback(isOnline));
  },
});
