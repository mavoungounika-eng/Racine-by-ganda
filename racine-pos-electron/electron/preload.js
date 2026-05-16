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
});
